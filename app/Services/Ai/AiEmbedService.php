<?php

namespace App\Services\Ai;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AiEmbedService
{
    public function __construct(
        protected AiManager $ai,
        protected AiContextResolver $context,
    ) {}

    /** @return array<string, array{label: string, description: string, params: array<string, string>}> */
    public function toolRegistry(): array
    {
        return [
            'draft_notice' => [
                'label'       => __('ai.tools.draft_notice'),
                'description' => __('ai.tools.draft_notice_desc'),
                'params'      => ['topic', 'audience', 'tone'],
            ],
            'translate' => [
                'label'       => __('ai.tools.translate'),
                'description' => __('ai.tools.translate_desc'),
                'params'      => ['text', 'target_lang'],
            ],
            'bulk_report_comments' => [
                'label'       => __('ai.tools.bulk_report_comments'),
                'description' => __('ai.tools.bulk_report_comments_desc'),
                'params'      => ['exam_id', 'class_section_id'],
            ],
            'draft_fee_reminder' => [
                'label'       => __('ai.tools.draft_fee_reminder'),
                'description' => __('ai.tools.draft_fee_reminder_desc'),
                'params'      => ['class_section_id', 'fee_structure_id', 'channel'],
            ],
            'draft_exam_reminder' => [
                'label'       => __('ai.tools.draft_exam_reminder'),
                'description' => __('ai.tools.draft_exam_reminder_desc'),
                'params'      => ['exam_id', 'class_section_id', 'channel'],
            ],
            'support_reply' => [
                'label'       => __('ai.tools.support_reply'),
                'description' => __('ai.tools.support_reply_desc'),
                'params'      => ['ticket_id', 'instruction'],
            ],
            'dashboard_briefing' => [
                'label'       => __('ai.tools.dashboard_briefing'),
                'description' => __('ai.tools.dashboard_briefing_desc'),
                'params'      => [],
            ],
            'invoice_insights' => [
                'label'       => __('ai.tools.invoice_insights'),
                'description' => __('ai.tools.invoice_insights_desc'),
                'params'      => ['invoice_id'],
            ],
            'exam_at_risk' => [
                'label'       => __('ai.tools.exam_at_risk'),
                'description' => __('ai.tools.exam_at_risk_desc'),
                'params'      => ['exam_id', 'class_section_id'],
            ],
            'student_summary' => [
                'label'       => __('ai.tools.student_summary'),
                'description' => __('ai.tools.student_summary_desc'),
                'params'      => ['student_id'],
            ],
            'page_help' => [
                'label'       => __('ai.tools.page_help'),
                'description' => __('ai.tools.page_help_desc'),
                'params'      => ['question', 'route_name', 'page_title'],
            ],
            'quick_chat' => [
                'label'       => __('ai.tools.quick_chat'),
                'description' => __('ai.tools.quick_chat_desc'),
                'params'      => ['message', 'page_title', 'route_name'],
            ],
        ];
    }

    public function run(string $tool, array $params, ?User $user = null): array
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            throw ValidationException::withMessages(['auth' => __('ai.not_authenticated')]);
        }

        $tool = strtolower(trim($tool));
        if (!array_key_exists($tool, $this->toolRegistry())) {
            throw ValidationException::withMessages(['tool' => __('ai.unknown_tool')]);
        }

        $institutionId = $this->context->institutionId($user);

        return match ($tool) {
            'draft_notice'         => $this->draftNotice($params, $institutionId),
            'translate'            => $this->translate($params),
            'bulk_report_comments' => $this->bulkReportComments($params, $institutionId, $user),
            'draft_fee_reminder'   => $this->draftFeeReminder($params, $institutionId),
            'draft_exam_reminder'  => $this->draftExamReminder($params, $institutionId),
            'support_reply'        => $this->supportReply($params, $user),
            'dashboard_briefing'   => $this->dashboardBriefing($institutionId, $user),
            'invoice_insights'     => $this->invoiceInsights($params, $institutionId, $user),
            'exam_at_risk'         => $this->examAtRisk($params, $institutionId, $user),
            'student_summary'      => $this->studentSummary($params, $institutionId, $user),
            'page_help'            => $this->pageHelp($params),
            'quick_chat'           => $this->quickChat($params, $user, $institutionId),
            default                => throw ValidationException::withMessages(['tool' => __('ai.unknown_tool')]),
        };
    }

    protected function draftNotice(array $params, ?int $institutionId): array
    {
        $v = Validator::make($params, [
            'topic'    => 'required|string|max:500',
            'audience' => 'nullable|string|max:100',
            'tone'     => 'nullable|string|max:50',
        ])->validate();

        $audience = $v['audience'] ?? 'all';
        $tone     = $v['tone'] ?? 'professional';

        $prompt = "Draft a school notice for parents and staff.\n"
            . "Topic: {$v['topic']}\nAudience: {$audience}\nTone: {$tone}\n"
            . "Include a clear title line on the first line, then the body. Keep it concise and actionable.";

        $text = $this->runPrompt('embed:draft_notice', 'You are a school communications assistant.', $prompt, $institutionId);

        return ['text' => $text, 'type' => 'notice_draft'];
    }

    protected function translate(array $params): array
    {
        $v = Validator::make($params, [
            'text'        => 'required|string|max:8000',
            'target_lang' => 'required|string|max:30',
        ])->validate();

        $prompt = "Translate the following text to {$v['target_lang']}. Return only the translation, no preamble:\n\n{$v['text']}";
        $text = $this->runPrompt('embed:translate', 'You are a professional translator for school communications.', $prompt);

        return ['text' => $text, 'type' => 'translation'];
    }

    protected function bulkReportComments(array $params, ?int $institutionId, User $user): array
    {
        $v = Validator::make($params, [
            'exam_id'          => 'required|integer',
            'class_section_id' => 'required|integer',
        ])->validate();

        $data = $this->context->examClassMarks(
            (int) $v['exam_id'],
            (int) $v['class_section_id'],
            $institutionId,
            $user
        );

        if (!$data || empty($data['students'])) {
            throw ValidationException::withMessages(['exam_id' => __('ai.no_marks_data')]);
        }

        $lines = ["Exam: {$data['exam']}"];
        foreach ($data['students'] as $s) {
            $markStr = collect($s['marks'])->map(fn ($m) => ($m['subject'] ?? '?') . ':' . ($m['marks'] ?? 'abs'))->implode(', ');
            $lines[] = "Student ID {$s['student_id']} | {$s['name']} | avg: " . ($s['average'] ?? 'n/a') . " | {$markStr}";
        }

        $prompt = "You are a school teacher writing report card comments.\n"
            . "For EACH student below, write ONE encouraging comment (2-3 sentences) based on their marks.\n"
            . "Format EXACTLY as:\nSTUDENT_ID: comment text\n\n"
            . implode("\n", $lines);

        $text = $this->runPrompt('embed:bulk_report_comments', 'You write balanced, encouraging report card comments.', $prompt, $institutionId);

        $comments = $this->parseStudentComments($text);

        return [
            'text'     => $text,
            'comments' => $comments,
            'type'     => 'report_comments',
        ];
    }

    protected function draftFeeReminder(array $params, ?int $institutionId): array
    {
        $v = Validator::make($params, [
            'class_section_id'  => 'nullable|integer',
            'fee_structure_id'  => 'nullable|integer',
            'channel'           => 'nullable|string|in:sms,email,both,whatsapp',
        ])->validate();

        $summary = $this->context->feesOverdueSummary(
            $institutionId,
            isset($v['class_section_id']) ? (int) $v['class_section_id'] : null,
            isset($v['fee_structure_id']) ? (int) $v['fee_structure_id'] : null,
        );

        $channel = $v['channel'] ?? 'sms';
        $limit   = $channel === 'sms' ? 320 : 600;

        $prompt = "Write a {$channel} fee reminder message for parents.\n"
            . "Overdue invoices: {$summary['count']}, total due approx {$summary['total_due']}.\n"
            . "Sample students: " . json_encode($summary['sample']) . "\n"
            . "Keep under {$limit} characters. Polite but clear. Include placeholder [Student Name] and [Amount].";

        $text = $this->runPrompt('embed:draft_fee_reminder', 'You draft parent fee reminder messages.', $prompt, $institutionId);

        return ['text' => $text, 'type' => 'fee_reminder', 'stats' => $summary];
    }

    protected function draftExamReminder(array $params, ?int $institutionId): array
    {
        $v = Validator::make($params, [
            'exam_id'          => 'nullable|integer',
            'class_section_id' => 'nullable|integer',
            'channel'          => 'nullable|string|in:sms,email,both,whatsapp',
        ])->validate();

        $channel = $v['channel'] ?? 'sms';
        $limit   = $channel === 'sms' ? 320 : 600;

        $prompt = "Write a {$channel} exam reminder for parents.\n"
            . "Exam ID: {$v['exam_id']}, class section ID: " . ($v['class_section_id'] ?? 'all') . ".\n"
            . "Keep under {$limit} chars. Mention date/time placeholders [Exam Date], [Subject].";

        $text = $this->runPrompt('embed:draft_exam_reminder', 'You draft parent exam reminder messages.', $prompt, $institutionId);

        return ['text' => $text, 'type' => 'exam_reminder'];
    }

    protected function supportReply(array $params, User $user): array
    {
        $v = Validator::make($params, [
            'ticket_id'   => 'required|integer',
            'instruction' => 'nullable|string|max:500',
        ])->validate();

        $thread = $this->context->ticketThread((int) $v['ticket_id'], $user);
        if (!$thread) {
            throw ValidationException::withMessages(['ticket_id' => __('ai.ticket_not_found')]);
        }

        $extra = $v['instruction'] ?? 'Be helpful and professional.';
        $prompt = "Draft a support reply for Digitex School Management.\n"
            . "Subject: {$thread['subject']}\nStatus: {$thread['status']}\n"
            . "Thread:\n" . collect($thread['messages'])->map(fn ($m) => "{$m['from']}: {$m['body']}")->implode("\n")
            . "\n\nInstruction: {$extra}\nReturn only the reply body.";

        $text = $this->runPrompt('embed:support_reply', 'You are Digitex support staff drafting helpful replies.', $prompt);

        return ['text' => $text, 'type' => 'support_reply'];
    }

    protected function dashboardBriefing(?int $institutionId, User $user): array
    {
        $facts = $this->context->dashboardBriefing($institutionId, $user);

        if (empty($facts['facts'])) {
            return [
                'text' => __('ai.dashboard_all_clear'),
                'type' => 'dashboard_briefing',
            ];
        }

        $prompt = "You are a school operations copilot. Summarize what needs attention today in 4-6 bullet points.\n"
            . "Be specific and actionable. Facts:\n- " . implode("\n- ", $facts['facts']);

        $text = $this->runPrompt('embed:dashboard_briefing', 'You are a school operations copilot.', $prompt, $institutionId);

        return ['text' => $text, 'type' => 'dashboard_briefing', 'facts' => $facts['facts']];
    }

    protected function invoiceInsights(array $params, ?int $institutionId, User $user): array
    {
        $v = Validator::make($params, ['invoice_id' => 'required|integer'])->validate();
        $ctx = $this->context->invoiceContext((int) $v['invoice_id'], $institutionId, $user);

        if (!$ctx) {
            throw ValidationException::withMessages(['invoice_id' => __('ai.invoice_not_found')]);
        }

        $prompt = "Explain this invoice to a school admin in plain language (3-5 bullets): payment status, amount due, suggested next steps for collection.\n"
            . json_encode($ctx, JSON_PRETTY_PRINT);

        $text = $this->runPrompt('embed:invoice_insights', 'You explain school invoices clearly to administrators.', $prompt, $institutionId);

        return ['text' => $text, 'type' => 'invoice_insights', 'context' => $ctx];
    }

    protected function examAtRisk(array $params, ?int $institutionId, User $user): array
    {
        $v = Validator::make($params, [
            'exam_id'          => 'required|integer',
            'class_section_id' => 'required|integer',
        ])->validate();

        $data = $this->context->examClassMarks(
            (int) $v['exam_id'],
            (int) $v['class_section_id'],
            $institutionId,
            $user
        );

        if (!$data) {
            throw ValidationException::withMessages(['exam_id' => __('ai.no_marks_data')]);
        }

        $atRisk = collect($data['students'])->filter(fn ($s) => $s['average'] !== null && $s['average'] < 50)->values();

        $prompt = "Analyze exam performance and list at-risk students with brief intervention suggestions.\n"
            . "Exam: {$data['exam']}\n"
            . "All students: " . json_encode($data['students']) . "\n"
            . "Focus on students with average below 50%.";

        $text = $this->runPrompt('embed:exam_at_risk', 'You analyze student exam performance for teachers.', $prompt, $institutionId);

        return [
            'text'        => $text,
            'type'        => 'exam_at_risk',
            'at_risk_count' => $atRisk->count(),
        ];
    }

    protected function studentSummary(array $params, ?int $institutionId, User $user): array
    {
        $v = Validator::make($params, ['student_id' => 'required|integer'])->validate();
        $data = $this->context->studentSummary((int) $v['student_id'], $institutionId, $user);

        if (!$data) {
            throw ValidationException::withMessages(['student_id' => __('ai.student_not_found')]);
        }

        $prompt = "Write a concise 360° student summary for a teacher (4-6 bullets): academics, attendance, fees, strengths, areas to watch.\n"
            . json_encode($data, JSON_PRETTY_PRINT);

        $text = $this->runPrompt('embed:student_summary', 'You summarize student profiles for teachers.', $prompt, $institutionId);

        return ['text' => $text, 'type' => 'student_summary', 'context' => $data];
    }

    protected function pageHelp(array $params): array
    {
        $v = Validator::make($params, [
            'question'   => 'required|string|max:1000',
            'route_name' => 'nullable|string|max:200',
            'page_title' => 'nullable|string|max:200',
        ])->validate();

        $help = $this->context->helpSnippets($v['route_name'] ?? null);
        $page = $v['page_title'] ?? 'Digitex SMS';

        $prompt = "Answer this question about using Digitex School Management on page \"{$page}\".\n"
            . "Use the help snippets when relevant. Give step-by-step UI navigation.\n\n"
            . "Help snippets:\n{$help}\n\nQuestion: {$v['question']}";

        $text = $this->runPrompt('embed:page_help', (new AiAssistantPrompt())->build(), $prompt);

        return ['text' => $text, 'type' => 'page_help'];
    }

    protected function quickChat(array $params, User $user, ?int $institutionId): array
    {
        $v = Validator::make($params, [
            'message'    => 'required|string|max:2000',
            'page_title' => 'nullable|string|max:200',
            'route_name' => 'nullable|string|max:200',
        ])->validate();

        $page = $v['page_title'] ?? 'current page';
        $route = $v['route_name'] ?? '';

        $system = (new AiAssistantPrompt())->build($user, $institutionId)
            . "\n\nThe user is on: {$page} (route: {$route}). Answer in context of that module when relevant.";

        $text = $this->runPrompt('embed:quick_chat', $system, $v['message'], $institutionId);

        return ['text' => $text, 'type' => 'quick_chat'];
    }

    protected function runPrompt(string $feature, string $system, string $userPrompt, ?int $institutionId = null, array $opts = []): string
    {
        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $userPrompt],
        ];
        $opts['institution_id'] = $institutionId;
        $result = $this->ai->ask($feature, $messages, $opts);

        if (!$result['ok']) {
            throw ValidationException::withMessages(['ai' => $this->errorMessage($result['error'])]);
        }

        return trim((string) ($result['content'] ?? ''));
    }

    protected function errorMessage(?string $error): string
    {
        return match ($error) {
            'quota_exceeded' => __('ai.error_quota'),
            'not_configured' => __('ai.error_not_configured'),
            'no_access'      => __('ai.no_access_message'),
            default          => __('ai.error_generic'),
        };
    }

    /** @return array<int, string> */
    protected function parseStudentComments(string $text): array
    {
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            if (preg_match('/^(\d+)\s*:\s*(.+)$/u', trim($line), $m)) {
                $out[(int) $m[1]] = trim($m[2]);
            }
        }
        return $out;
    }
}
