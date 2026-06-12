<?php

namespace App\Services\Ai;

use App\Models\AiUsageLog;
use App\Models\InstitutionSetting;
use App\Models\Subscription;
use App\Services\PlanContextService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

/**
 * Single safe entry point for all AI behaviour: access (plan) checks, quota
 * metering, credential resolution, model calls and usage logging.
 *
 * Every public method is defensive — if the AI tables/columns do not exist yet
 * (e.g. before the migration runs) or anything else fails, methods degrade to
 * "AI unavailable" instead of throwing. This guarantees the rest of the
 * application keeps working for schools without an AI plan.
 */
class AiManager
{
    public function __construct(
        protected AiClient $client,
        protected PlanContextService $plans,
    ) {}

    /* ---------------------------------------------------------------------
     | Context helpers
     * ------------------------------------------------------------------- */

    public function isMasterEnabled(): bool
    {
        return $this->plans->isAiPlatformEnabled();
    }

    /**
     * Resolve the institution id for the current authenticated user, mirroring
     * BaseController's context resolution without coupling to it.
     */
    public function resolveInstitutionId(): ?int
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $activeId = session('active_institution_id');

        if (($activeId === 'global' || $activeId === 0 || $activeId === '0') && $this->isSuperAdmin()) {
            return null;
        }

        if (!empty($activeId) && is_numeric($activeId)) {
            return (int) $activeId;
        }

        return $user->institute_id ? (int) $user->institute_id : null;
    }

    public function isSuperAdmin(): bool
    {
        $user = Auth::user();
        return $user ? $user->hasRole('Super Admin') : false;
    }

    /* ---------------------------------------------------------------------
     | Access & quota
     * ------------------------------------------------------------------- */

    /**
     * Does the current context have AI on its plan (or a manual override)?
     */
    public function hasPlanAccess(?int $institutionId): bool
    {
        if (!$this->isMasterEnabled()) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$institutionId) {
            return false;
        }

        try {
            $package = $this->activePackage($institutionId);
            return $this->plans->packageGrantsAi($package, $institutionId);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function isUnlimited(?int $institutionId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        if (!$institutionId) {
            return false;
        }
        try {
            $package = $this->activePackage($institutionId);
            return $this->plans->packageAiUnlimited($package, $institutionId);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function monthlyLimit(?int $institutionId): int
    {
        $default = (int) config('ai.default_monthly_limit', 100);
        if (!$institutionId) {
            return $default;
        }
        try {
            $package = $this->activePackage($institutionId);
            if ($package && Schema::hasColumn('packages', 'ai_monthly_limit') && $package->ai_monthly_limit !== null) {
                return (int) $package->ai_monthly_limit;
            }
        } catch (\Throwable $e) {
            // fall through
        }
        return $default;
    }

    public function usageThisPeriod(?int $institutionId): int
    {
        try {
            return AiUsageLog::query()
                ->where('period', $this->currentPeriod())
                ->where('status', 'success')
                ->when($institutionId, fn ($q) => $q->where('institution_id', $institutionId))
                ->when(!$institutionId, fn ($q) => $q->whereNull('institution_id'))
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Remaining requests this period, or null when unlimited.
     */
    public function remaining(?int $institutionId): ?int
    {
        if ($this->isUnlimited($institutionId)) {
            return null;
        }
        return max(0, $this->monthlyLimit($institutionId) - $this->usageThisPeriod($institutionId));
    }

    public function currentPeriod(): string
    {
        return now()->format('Y-m');
    }

    /* ---------------------------------------------------------------------
     | Credentials
     * ------------------------------------------------------------------- */

    public function isConfigured(?int $institutionId): bool
    {
        $creds = $this->resolveCredentials($institutionId);
        return !empty($creds['key']);
    }

    /**
     * Resolve API credentials: school BYO key (Enterprise) → platform key → env.
     */
    public function resolveCredentials(?int $institutionId): array
    {
        $key     = null;
        $model   = config('ai.model');
        $baseUrl = config('ai.base_url');

        try {
            // 1. School-owned key
            if ($institutionId) {
                $byo = InstitutionSetting::get($institutionId, 'ai_api_key');
                if (!empty($byo)) {
                    $key = $this->decrypt($byo);
                }
                $model   = InstitutionSetting::get($institutionId, 'ai_model') ?: $model;
                $baseUrl = InstitutionSetting::get($institutionId, 'ai_base_url') ?: $baseUrl;
            }

            // 2. Platform key (institution_id NULL)
            if (empty($key)) {
                $platform = InstitutionSetting::get(null, 'ai_api_key');
                if (!empty($platform)) {
                    $key = $this->decrypt($platform);
                }
                $model   = InstitutionSetting::get(null, 'ai_model') ?: $model;
                $baseUrl = InstitutionSetting::get(null, 'ai_base_url') ?: $baseUrl;
            }
        } catch (\Throwable $e) {
            // fall through to env
        }

        // 3. Environment fallback
        if (empty($key)) {
            $key = config('ai.api_key');
        }

        return ['key' => $key, 'model' => $model, 'base_url' => $baseUrl];
    }

    protected function decrypt(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            // Stored as plain text (legacy / manual entry)
            return $value;
        }
    }

    /* ---------------------------------------------------------------------
     | Orchestration
     * ------------------------------------------------------------------- */

    /**
     * Run an AI request end to end: authorize, call provider, meter usage.
     *
     * @return array ['ok'=>bool, 'content'=>?string, 'error'=>?string, 'remaining'=>?int]
     */
    public function ask(string $feature, array $messages, array $opts = []): array
    {
        $institutionId = $opts['institution_id'] ?? $this->resolveInstitutionId();
        $userId        = Auth::id();

        if (!$this->hasPlanAccess($institutionId)) {
            return ['ok' => false, 'content' => null, 'error' => 'no_access', 'remaining' => 0];
        }

        $remaining = $this->remaining($institutionId);
        if ($remaining !== null && $remaining <= 0) {
            $this->log($institutionId, $userId, $feature, null, ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0], 'blocked');
            return ['ok' => false, 'content' => null, 'error' => 'quota_exceeded', 'remaining' => 0];
        }

        $creds = $this->resolveCredentials($institutionId);
        if (empty($creds['key'])) {
            return ['ok' => false, 'content' => null, 'error' => 'not_configured', 'remaining' => $remaining];
        }

        $result = $this->client->chat($messages, $creds, $opts);

        $this->log(
            $institutionId,
            $userId,
            $feature,
            $creds['model'] ?? null,
            $result['usage'] ?? [],
            $result['ok'] ? 'success' : 'error'
        );

        $result['remaining'] = $this->remaining($institutionId);
        return $result;
    }

    protected function log(?int $institutionId, ?int $userId, string $feature, ?string $model, array $usage, string $status): void
    {
        try {
            AiUsageLog::create([
                'institution_id'    => $institutionId,
                'user_id'           => $userId,
                'feature'           => $feature,
                'model'             => $model,
                'prompt_tokens'     => $usage['prompt_tokens'] ?? 0,
                'completion_tokens' => $usage['completion_tokens'] ?? 0,
                'total_tokens'      => $usage['total_tokens'] ?? 0,
                'status'            => $status,
                'period'            => $this->currentPeriod(),
            ]);
        } catch (\Throwable $e) {
            // Logging must never break the request.
        }
    }

    protected function activePackage(int $institutionId)
    {
        $subscription = Subscription::with('package')
            ->where('institution_id', $institutionId)
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'pending_payment' THEN 1 WHEN 'expired' THEN 2 ELSE 3 END")
            ->orderByDesc('end_date')
            ->orderByDesc('id')
            ->first();

        return $subscription?->package;
    }

    /* ---------------------------------------------------------------------
     | Prompt builders for the AI Studio tools
     * ------------------------------------------------------------------- */

    /**
     * @return array|null  [messages, opts] or null for an unknown tool
     */
    public function buildToolMessages(string $tool, array $inputs): ?array
    {
        $text = trim($inputs['text'] ?? '');
        $tone = trim($inputs['tone'] ?? 'professional');
        $lang = trim($inputs['language'] ?? '');

        switch ($tool) {
            case 'draft_notice':
                return [[
                    ['role' => 'system', 'content' => 'You are a school communications assistant. Write clear, well-structured announcements for parents, students and staff. Keep them concise and ready to publish. Do not invent specific dates, amounts or names that were not provided.'],
                    ['role' => 'user', 'content' => "Write a school notice/announcement in a {$tone} tone about the following. Include a short title line.\n\n{$text}"],
                ], ['temperature' => 0.7]];

            case 'report_comment':
                return [[
                    ['role' => 'system', 'content' => 'You are an experienced teacher writing report-card comments. Write balanced, encouraging and specific feedback. Avoid generic filler. 2-4 sentences.'],
                    ['role' => 'user', 'content' => "Write a report-card comment based on these notes about the student's performance:\n\n{$text}"],
                ], ['temperature' => 0.6, 'max_tokens' => 300]];

            case 'translate':
                $target = $lang ?: 'French';
                return [[
                    ['role' => 'system', 'content' => 'You are a professional translator. Translate accurately and naturally, preserving meaning and tone. Return only the translation.'],
                    ['role' => 'user', 'content' => "Translate the following text into {$target}:\n\n{$text}"],
                ], ['temperature' => 0.3]];

            case 'summarize':
                return [[
                    ['role' => 'system', 'content' => 'You summarise text into clear, concise bullet points capturing the key information.'],
                    ['role' => 'user', 'content' => "Summarise the following:\n\n{$text}"],
                ], ['temperature' => 0.3]];

            case 'improve':
                return [[
                    ['role' => 'system', 'content' => 'You are a writing assistant. Improve grammar, clarity and tone of the text while preserving its meaning. Return only the improved text.'],
                    ['role' => 'user', 'content' => "Rewrite this in a {$tone} tone, fixing any grammar or clarity issues:\n\n{$text}"],
                ], ['temperature' => 0.5]];

            case 'support_reply':
                return [[
                    ['role' => 'system', 'content' => 'You are a helpful support agent for a school management SaaS called Digitex. Draft a polite, professional reply to the user message. Be concise and solution-oriented. Do not promise specific timelines.'],
                    ['role' => 'user', 'content' => "Draft a support reply to this message:\n\n{$text}"],
                ], ['temperature' => 0.6]];

            default:
                return null;
        }
    }
}
