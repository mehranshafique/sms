<?php

namespace App\Services;

use App\Enums\AcademicType;
use App\Models\AcademicSession;
use App\Models\AssessmentPeriodState;
use App\Models\InstitutionSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AssessmentPeriodService
{
    public const PERIOD_KEYS = [
        'p1', 'p2', 'p3', 'p4', 'p5', 'p6',
        'trimester_exam_1', 'trimester_exam_2', 'trimester_exam_3',
        'semester_exam_1', 'semester_exam_2',
    ];

    public const TERM_CHILDREN = [
        'trimester_1' => ['p1', 'p2', 'trimester_exam_1'],
        'trimester_2' => ['p3', 'p4', 'trimester_exam_2'],
        'trimester_3' => ['p5', 'p6', 'trimester_exam_3'],
        'semester_1' => ['p1', 'p2', 'semester_exam_1'],
        'semester_2' => ['p3', 'p4', 'semester_exam_2'],
    ];

    public function __construct(protected AcademicCycleService $cycleService)
    {
    }

    /**
     * @return list<string>
     */
    public function closableKeys(): array
    {
        return self::PERIOD_KEYS;
    }

    public function isPeriodKey(string $key): bool
    {
        return (bool) preg_match('/^p[1-6]$/', $key);
    }

    public function isExamKey(string $key): bool
    {
        return (bool) preg_match('/^(trimester|semester)_exam_[1-3]$/', $key);
    }

    public function isTermKey(string $key): bool
    {
        return array_key_exists($key, self::TERM_CHILDREN);
    }

    public function isClosableKey(string $key): bool
    {
        return in_array($key, self::PERIOD_KEYS, true);
    }

    /**
     * @return list<string>
     */
    public function childKeysForTerm(string $termKey): array
    {
        return self::TERM_CHILDREN[$termKey] ?? [];
    }

    public function termKeyForCycle(string $cycle, int $termNumber): ?string
    {
        if ($this->cycleService->usesTrimesterModel($cycle)) {
            return 'trimester_' . $termNumber;
        }

        if ($this->cycleService->usesSemesterModel($cycle)) {
            return 'semester_' . $termNumber;
        }

        return null;
    }

    /**
     * Chronological official stages for a cycle (periods, exams, then derived terms).
     *
     * @return list<string>
     */
    public function stageOrder(string $cycle): array
    {
        if ($this->cycleService->usesTrimesterModel($cycle)) {
            return [
                'p1', 'p2', 'trimester_exam_1', 'trimester_1',
                'p3', 'p4', 'trimester_exam_2', 'trimester_2',
                'p5', 'p6', 'trimester_exam_3', 'trimester_3',
            ];
        }

        if ($this->cycleService->usesSemesterModel($cycle)) {
            return [
                'p1', 'p2', 'semester_exam_1', 'semester_1',
                'p3', 'p4', 'semester_exam_2', 'semester_2',
            ];
        }

        return [];
    }

    public function currentSessionId(int $institutionId): ?int
    {
        return AcademicSession::where('institution_id', $institutionId)
            ->where('is_current', true)
            ->value('id');
    }

    public function getOrCreateState(int $institutionId, int $sessionId, string $periodKey): AssessmentPeriodState
    {
        return AssessmentPeriodState::firstOrCreate(
            [
                'institution_id' => $institutionId,
                'academic_session_id' => $sessionId,
                'period_key' => $periodKey,
            ],
            ['status' => AssessmentPeriodState::STATUS_OPEN]
        );
    }

    public function status(int $institutionId, int $sessionId, string $periodKey): string
    {
        if ($this->isTermKey($periodKey)) {
            return $this->termClosed($institutionId, $sessionId, $periodKey)
                ? AssessmentPeriodState::STATUS_CLOSED
                : AssessmentPeriodState::STATUS_OPEN;
        }

        $row = AssessmentPeriodState::where('institution_id', $institutionId)
            ->where('academic_session_id', $sessionId)
            ->where('period_key', $periodKey)
            ->first();

        return $row?->status ?? AssessmentPeriodState::STATUS_OPEN;
    }

    public function isClosed(int $institutionId, int $sessionId, string $periodKey): bool
    {
        if ($this->isTermKey($periodKey)) {
            return $this->termClosed($institutionId, $sessionId, $periodKey);
        }

        return $this->status($institutionId, $sessionId, $periodKey) === AssessmentPeriodState::STATUS_CLOSED;
    }

    public function isOfficial(int $institutionId, int $sessionId, string $periodKey): bool
    {
        return $this->isClosed($institutionId, $sessionId, $periodKey);
    }

    public function isReopened(int $institutionId, int $sessionId, string $periodKey): bool
    {
        if ($this->isTermKey($periodKey)) {
            foreach ($this->childKeysForTerm($periodKey) as $child) {
                if ($this->status($institutionId, $sessionId, $child) === AssessmentPeriodState::STATUS_REOPENED) {
                    return true;
                }
            }

            return false;
        }

        return $this->status($institutionId, $sessionId, $periodKey) === AssessmentPeriodState::STATUS_REOPENED;
    }

    public function isAdminViewable(int $institutionId, int $sessionId, string $periodKey): bool
    {
        return $this->isOfficial($institutionId, $sessionId, $periodKey)
            || $this->isReopened($institutionId, $sessionId, $periodKey);
    }

    public function allowsMarksEntry(int $institutionId, int $sessionId, string $periodKey): bool
    {
        if (! $this->isClosableKey($periodKey)) {
            return true;
        }

        $status = $this->status($institutionId, $sessionId, $periodKey);

        return in_array($status, [
            AssessmentPeriodState::STATUS_OPEN,
            AssessmentPeriodState::STATUS_REOPENED,
        ], true);
    }

    public function termClosed(int $institutionId, int $sessionId, string $termKey): bool
    {
        $children = $this->childKeysForTerm($termKey);
        if ($children === []) {
            return false;
        }

        foreach ($children as $child) {
            if (! $this->isClosed($institutionId, $sessionId, $child)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Latest officially closed stage for the cycle, or null if none.
     */
    public function latestOfficialStage(int $institutionId, int $sessionId, string $cycle): ?array
    {
        $latest = null;

        foreach ($this->stageOrder($cycle) as $key) {
            if ($this->isOfficial($institutionId, $sessionId, $key)) {
                $latest = $this->describeStage($key, $cycle);
            }
        }

        return $latest;
    }

    /**
     * @return array{
     *   key: string,
     *   type: string,
     *   period: ?string,
     *   trimester: ?int,
     *   semester: ?int,
     *   label: string,
     *   params: array<string, mixed>
     * }
     */
    public function describeStage(string $stageKey, string $cycle): array
    {
        $params = $this->reportParamsForStage($stageKey);
        $type = $params['type'] ?? 'period';

        return [
            'key' => $stageKey,
            'type' => $type,
            'period' => $params['period'] ?? null,
            'trimester' => $params['trimester'] ?? null,
            'semester' => $params['semester'] ?? null,
            'label' => $this->label($stageKey),
            'params' => $params,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function reportParamsForStage(string $stageKey): array
    {
        if ($this->isPeriodKey($stageKey) || $this->isExamKey($stageKey)) {
            return ['type' => 'period', 'period' => $stageKey];
        }

        if (preg_match('/^trimester_(\d)$/', $stageKey, $m)) {
            return ['type' => 'term', 'trimester' => (int) $m[1]];
        }

        if (preg_match('/^semester_(\d)$/', $stageKey, $m)) {
            return ['type' => 'term', 'semester' => (int) $m[1]];
        }

        return [];
    }

    public function resolveFromRequest(Request $request, string $cycle): ?array
    {
        if ($request->filled('stage_key')) {
            return $this->describeStage((string) $request->input('stage_key'), $cycle);
        }

        $period = (string) $request->input('period', '');
        $trimester = $request->filled('trimester') ? (int) $request->trimester : null;
        $semester = $request->filled('semester') ? (int) $request->semester : null;

        if ($period !== '' && $trimester === null && $semester === null) {
            return $this->describeStage($period, $cycle);
        }

        if ($trimester) {
            return $this->describeStage('trimester_' . $trimester, $cycle);
        }

        if ($semester) {
            return $this->describeStage('semester_' . $semester, $cycle);
        }

        return null;
    }

    public function label(string $key): string
    {
        return match (true) {
            (bool) preg_match('/^p(\d)$/', $key, $m) => __('reports.period') . ' ' . $m[1],
            $key === 'trimester_exam_1' => __('reports.exam_stage_trimester', ['n' => 1]),
            $key === 'trimester_exam_2' => __('reports.exam_stage_trimester', ['n' => 2]),
            $key === 'trimester_exam_3' => __('reports.exam_stage_trimester', ['n' => 3]),
            $key === 'semester_exam_1' => __('reports.exam_stage_semester', ['n' => 1]),
            $key === 'semester_exam_2' => __('reports.exam_stage_semester', ['n' => 2]),
            $key === 'trimester_1' => __('reports.trimester') . ' 1',
            $key === 'trimester_2' => __('reports.trimester') . ' 2',
            $key === 'trimester_3' => __('reports.trimester') . ' 3',
            $key === 'semester_1' => __('reports.semester') . ' 1',
            $key === 'semester_2' => __('reports.semester') . ' 2',
            default => strtoupper(str_replace('_', ' ', $key)),
        };
    }

    public function stageTitle(string $stageKey, string $cycle): string
    {
        if ($this->isPeriodKey($stageKey)) {
            return __('reports.bulletin_period_title', ['period' => strtoupper($stageKey)]);
        }

        if (preg_match('/^trimester_exam_(\d)$/', $stageKey, $m)) {
            return __('reports.bulletin_exam_trimester_title', ['n' => $m[1]]);
        }

        if (preg_match('/^semester_exam_(\d)$/', $stageKey, $m)) {
            return __('reports.bulletin_exam_semester_title', ['n' => $m[1]]);
        }

        if (preg_match('/^trimester_(\d)$/', $stageKey, $m)) {
            return $this->cycleService->termTitle(AcademicType::PRIMARY->value, (int) $m[1]);
        }

        if (preg_match('/^semester_(\d)$/', $stageKey, $m)) {
            return $this->cycleService->termTitle($cycle, (int) $m[1]);
        }

        return $this->label($stageKey);
    }

    public function cardsPerPage(string $stageKey, int $subjectCount = 0): int
    {
        if ($this->isTermKey($stageKey)) {
            return $subjectCount > 12 ? 2 : 3;
        }

        return 4;
    }

    public function close(int $institutionId, int $sessionId, string $periodKey, ?int $userId = null, bool $auto = false): AssessmentPeriodState
    {
        if (! $this->isClosableKey($periodKey)) {
            throw new \InvalidArgumentException("Cannot close derived stage {$periodKey}");
        }

        $state = $this->getOrCreateState($institutionId, $sessionId, $periodKey);
        $old = $state->status;

        $state->fill([
            'status' => AssessmentPeriodState::STATUS_CLOSED,
            'closed_at' => now(),
            'closed_by' => $userId ?? Auth::id(),
        ])->save();

        $this->syncActivePeriods($institutionId, $sessionId);

        AuditLogger::log(
            $auto ? 'auto_close' : 'close',
            'AssessmentPeriod',
            ($auto ? 'Auto-closed' : 'Closed') . " assessment stage {$periodKey}",
            ['status' => $old],
            ['status' => AssessmentPeriodState::STATUS_CLOSED, 'period_key' => $periodKey]
        );

        return $state->fresh();
    }

    public function reopen(int $institutionId, int $sessionId, string $periodKey, string $reason, ?int $userId = null): AssessmentPeriodState
    {
        if (! $this->isClosableKey($periodKey)) {
            throw new \InvalidArgumentException("Cannot reopen derived stage {$periodKey}");
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('A reopen reason is required.');
        }

        $state = $this->getOrCreateState($institutionId, $sessionId, $periodKey);
        $old = $state->status;

        $state->fill([
            'status' => AssessmentPeriodState::STATUS_REOPENED,
            'reopened_at' => now(),
            'reopened_by' => $userId ?? Auth::id(),
            'reopen_reason' => $reason,
            'revision_token' => ((int) $state->revision_token) + 1,
        ])->save();

        $this->syncActivePeriods($institutionId, $sessionId);

        AuditLogger::log(
            'reopen',
            'AssessmentPeriod',
            "Reopened assessment stage {$periodKey}: {$reason}",
            ['status' => $old],
            ['status' => AssessmentPeriodState::STATUS_REOPENED, 'period_key' => $periodKey, 'reason' => $reason]
        );

        return $state->fresh();
    }

    public function scheduleClose(int $institutionId, int $sessionId, string $periodKey, ?Carbon $closesAt): AssessmentPeriodState
    {
        $state = $this->getOrCreateState($institutionId, $sessionId, $periodKey);
        $state->closes_at = $closesAt;
        $state->save();

        return $state;
    }

    /**
     * @return list<AssessmentPeriodState>
     */
    public function dueAutoClose(): array
    {
        return AssessmentPeriodState::query()
            ->whereIn('status', [AssessmentPeriodState::STATUS_OPEN, AssessmentPeriodState::STATUS_REOPENED])
            ->whereNotNull('closes_at')
            ->where('closes_at', '<=', now())
            ->get()
            ->all();
    }

    public function syncActivePeriods(int $institutionId, int $sessionId): void
    {
        $existing = json_decode((string) InstitutionSetting::get($institutionId, 'active_periods', '[]'), true);
        $existing = is_array($existing) ? $existing : [];

        $closedKeys = AssessmentPeriodState::query()
            ->where('institution_id', $institutionId)
            ->where('academic_session_id', $sessionId)
            ->where('status', AssessmentPeriodState::STATUS_CLOSED)
            ->pluck('period_key')
            ->all();

        $reopenedKeys = AssessmentPeriodState::query()
            ->where('institution_id', $institutionId)
            ->where('academic_session_id', $sessionId)
            ->where('status', AssessmentPeriodState::STATUS_REOPENED)
            ->pluck('period_key')
            ->all();

        $synced = array_values(array_unique(array_diff(
            array_merge($existing, $reopenedKeys),
            $closedKeys
        )));

        InstitutionSetting::set($institutionId, 'active_periods', json_encode($synced), 'academic');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function dashboardRows(int $institutionId, int $sessionId, ?string $institutionType = null): array
    {
        $keys = $this->cycleService->examCategoriesForInstitutionType($institutionType);
        $keys = array_values(array_intersect($keys, self::PERIOD_KEYS));
        if ($keys === []) {
            $keys = self::PERIOD_KEYS;
        }

        $states = AssessmentPeriodState::with(['closer', 'reopener'])
            ->where('institution_id', $institutionId)
            ->where('academic_session_id', $sessionId)
            ->whereIn('period_key', $keys)
            ->get()
            ->keyBy('period_key');

        $rows = [];
        foreach ($keys as $key) {
            $state = $states->get($key);
            $status = $state?->status ?? AssessmentPeriodState::STATUS_OPEN;
            $rows[] = [
                'key' => $key,
                'label' => $this->label($key),
                'status' => $status,
                'closes_at' => $state?->closes_at,
                'closed_at' => $state?->closed_at,
                'closed_by' => $state?->closer?->name,
                'reopened_at' => $state?->reopened_at,
                'reopened_by' => $state?->reopener?->name,
                'reopen_reason' => $state?->reopen_reason,
                'revision_token' => (int) ($state?->revision_token ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * Payload for the admin report form.
     *
     * @return array<string, mixed>
     */
    public function scopeOptions(int $institutionId, int $sessionId, string $cycle): array
    {
        $base = $this->cycleService->scopeOptionsPayload($cycle);
        $periodKeys = $this->cycleService->allowedPeriodKeys($cycle);
        $examKeys = array_values(array_diff(
            $this->cycleService->examCategoriesForCycle($cycle),
            $periodKeys
        ));

        $periods = [];
        foreach (array_merge($periodKeys, $examKeys) as $key) {
            $status = $this->status($institutionId, $sessionId, $key);
            $periods[] = [
                'key' => $key,
                'label' => $this->label($key),
                'status' => $status,
                'selectable' => in_array($status, [
                    AssessmentPeriodState::STATUS_CLOSED,
                    AssessmentPeriodState::STATUS_REOPENED,
                ], true),
            ];
        }

        $terms = [];
        $maxTerm = $this->cycleService->usesTrimesterModel($cycle) ? 3 : ($this->cycleService->usesSemesterModel($cycle) ? 2 : 0);
        $prefix = $this->cycleService->usesTrimesterModel($cycle) ? 'trimester_' : 'semester_';
        for ($n = 1; $n <= $maxTerm; $n++) {
            $key = $prefix . $n;
            $closed = $this->termClosed($institutionId, $sessionId, $key);
            $reopened = $this->isReopened($institutionId, $sessionId, $key);
            $terms[] = [
                'number' => $n,
                'key' => $key,
                'label' => $this->label($key),
                'closed' => $closed,
                'reopened' => $reopened,
                'selectable' => $closed || $reopened,
            ];
        }

        return array_merge($base, [
            'period_stages' => $periods,
            'term_stages' => $terms,
            'latest_official' => $this->latestOfficialStage($institutionId, $sessionId, $cycle),
        ]);
    }

    public function unavailableMessage(string $requestedLabel, ?array $latest): string
    {
        if ($latest) {
            return __('reports.stage_not_available_with_latest', [
                'requested' => $requestedLabel,
                'latest' => $latest['label'],
            ]);
        }

        return __('reports.stage_not_available', ['requested' => $requestedLabel]);
    }
}
