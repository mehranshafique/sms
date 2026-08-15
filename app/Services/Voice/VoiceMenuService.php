<?php

namespace App\Services\Voice;

use App\Models\Student;
use App\Models\VoiceSession;
use Illuminate\Support\Facades\Log;

class VoiceMenuService
{
    public function __construct(
        protected VoiceIdentityService $identity,
        protected VoiceAnswerService $answers,
        protected VoiceSessionService $sessions,
        protected VoicePinService $pins,
        protected VoiceAiAgentService $agent,
        protected VoiceTransferService $transfers,
        protected InfobipCmlBuilder $cml
    ) {
    }

    /**
     * @return array{actions: list<array<string, mixed>>}
     */
    public function welcome(VoiceSession $session): array
    {
        $this->identifyCaller($session);
        $session = $session->fresh();
        $prompt = $this->menuPrompt($session);
        $session->update([
            'state' => $session->isParentMenu()
                ? VoiceSession::STATE_PARENT_MENU
                : VoiceSession::STATE_GUEST_MENU,
        ]);

        return $this->cml->sayAndCapture($prompt, $session->locale);
    }

    /**
     * @return array{actions: list<array<string, mixed>>}
     */
    public function handleDigit(VoiceSession $session, string $digit): array
    {
        $digit = trim($digit);
        $this->sessions->bumpTurn($session, $digit);

        if (! $session->institution_id) {
            return $this->cml->sayAndHangup(__('voice.unavailable', [], $session->locale), $session->locale);
        }

        if ($session->turns > $this->sessions->maxTurns((int) $session->institution_id)) {
            $this->sessions->end($session, 'max_turns');

            return $this->cml->sayAndHangup(__('voice.max_turns', [], $session->locale), $session->locale);
        }

        if ($digit === '' || $digit === 'timeout') {
            return $this->repeatMenu($session, __('voice.no_input', [], $session->locale));
        }

        if ($digit === '*') {
            $this->sessions->end($session, 'hangup_digit');

            return $this->cml->sayAndHangup(__('voice.goodbye', [], $session->locale), $session->locale);
        }

        return match ($session->state) {
            VoiceSession::STATE_PIN_ENTRY => $this->handlePinEntry($session, $digit),
            VoiceSession::STATE_SELECT_CHILD => $this->handleChildSelect($session, $digit),
            VoiceSession::STATE_MORE_MENU => $this->handleMoreMenu($session, $digit),
            default => $session->isParentMenu()
                ? $this->handleParentDigit($session, $digit)
                : $this->handleGuestDigit($session, $digit),
        };
    }

    /**
     * Phase 3: the caller's recorded question has been transcribed.
     *
     * @return array{actions: list<array<string, mixed>>}
     */
    public function handleTranscribedQuestion(VoiceSession $session, ?string $question): array
    {
        $locale = $session->locale;

        if ($question === null || trim($question) === '') {
            return $this->answerThenMenu($session, __('voice.ai_not_understood', [], $locale));
        }

        $result = $this->agent->answer($session, $question);

        if (! $result['ok']) {
            return $this->answerThenMenu($session, __('voice.ai_unavailable', [], $locale));
        }

        Log::info('Voice AI answer delivered', [
            'call_id' => $session->call_id,
            'institution_id' => $session->institution_id,
        ]);

        $session = $session->fresh();
        $suffix = $this->agent->questionsExhausted($session)
            ? ''
            : ' ' . __('voice.ai_more', [], $locale);

        return $this->answerThenMenu($session, trim($result['answer'] . $suffix));
    }

    public function identifyCaller(VoiceSession $session): void
    {
        if (! $session->institution_id) {
            return;
        }

        $parent = $this->identity->findParent((int) $session->institution_id, $session->phone_number);
        if (! $parent) {
            $session->update([
                'menu_profile' => 'guest',
                'parent_id' => null,
                'user_id' => null,
                'student_id' => null,
            ]);

            return;
        }

        $children = $this->identity->childrenForParent($parent, (int) $session->institution_id);
        $first = $children->first();

        $session->update([
            'menu_profile' => 'parent',
            'parent_id' => $parent->id,
            'user_id' => $parent->user_id,
            'student_id' => $first?->id,
            'state' => VoiceSession::STATE_PARENT_MENU,
        ]);
    }

    public function menuPrompt(VoiceSession $session): string
    {
        $school = $this->identity->schoolName($session->institution_id);
        $locale = $session->locale;
        app()->setLocale($locale);

        $parts = [];

        if ($session->isParentMenu()) {
            $child = $session->student_id ? Student::find($session->student_id) : null;
            $childName = $child?->first_name ?: __('voice.your_child', [], $locale);

            $parts[] = __('voice.parent_menu_intro', ['school' => $school, 'child' => $childName], $locale);
            $parts[] = __('voice.parent_menu_options', [], $locale);

            if ($this->moreOptions($session) !== []) {
                $parts[] = __('voice.menu_more', [], $locale);
            }

            $parts[] = __('voice.parent_menu_tail', [], $locale);

            return implode(' ', $parts);
        }

        $parts[] = __('voice.guest_menu_intro', ['school' => $school], $locale);
        $parts[] = __('voice.guest_menu_options', [], $locale);

        if ($this->moreOptions($session) !== []) {
            $parts[] = __('voice.menu_more', [], $locale);
        }

        $parts[] = __('voice.guest_menu_tail', [], $locale);

        return implode(' ', $parts);
    }

    /**
     * @return array{actions: list<array<string, mixed>>}
     */
    protected function handleGuestDigit(VoiceSession $session, string $digit): array
    {
        $locale = $session->locale;
        $institutionId = (int) $session->institution_id;

        return match ($digit) {
            '1' => $this->answerThenMenu($session, $this->answers->guestInfo($institutionId, 'admission', $locale)),
            '2' => $this->answerThenMenu($session, $this->answers->guestInfo($institutionId, 'fees', $locale)),
            '3' => $this->answerThenMenu($session, $this->answers->guestInfo($institutionId, 'contact', $locale)),
            '4' => $this->answerThenMenu($session, $this->answers->portalHelp($locale)),
            '6' => $this->startMoreMenu($session),
            '9' => $this->toggleLocale($session),
            '0' => $this->repeatMenu($session),
            default => $this->repeatMenu($session, __('voice.invalid', [], $locale)),
        };
    }

    /**
     * @return array{actions: list<array<string, mixed>>}
     */
    protected function handleParentDigit(VoiceSession $session, string $digit): array
    {
        $locale = $session->locale;

        return match ($digit) {
            '1' => $this->runIntent($session, 'fees'),
            '2' => $this->runIntent($session, 'attendance'),
            '3' => $this->runIntent($session, 'notice'),
            '4' => $this->runIntent($session, 'ptm'),
            '5' => $this->startChildSelect($session),
            '6' => $this->startMoreMenu($session),
            '8' => $this->runIntent($session, 'contact'),
            '9' => $this->toggleLocale($session),
            '0' => $this->repeatMenu($session),
            default => $this->repeatMenu($session, __('voice.invalid', [], $locale)),
        };
    }

    /**
     * Options that only exist once a school turns them on (Phase 2–4 extras).
     *
     * @return array<string, string> digit => intent
     */
    protected function moreOptions(VoiceSession $session): array
    {
        $intents = [];

        if ($session->isParentMenu()) {
            $intents[] = 'pickup';
            $intents[] = 'requests';
            $intents[] = 'fee_details';
        }

        if ($this->agent->isAvailableFor($session)) {
            $intents[] = 'ai';
        }

        if ($this->transfers->isEnabled($session->institution_id)) {
            $intents[] = 'agent';
        }

        $map = [];
        foreach (array_values($intents) as $index => $intent) {
            $map[(string) ($index + 1)] = $intent;
        }

        return $map;
    }

    /**
     * @return array{actions: list<array<string, mixed>>}
     */
    protected function startMoreMenu(VoiceSession $session): array
    {
        $locale = $session->locale;
        $map = $this->moreOptions($session);

        if ($map === []) {
            return $this->repeatMenu($session, __('voice.invalid', [], $locale));
        }

        $lines = [__('voice.more.intro', [], $locale)];
        foreach ($map as $digit => $intent) {
            $lines[] = __('voice.more.' . $intent, ['digit' => $digit], $locale);
        }
        $lines[] = __('voice.more.back', [], $locale);

        $session->update([
            'state' => VoiceSession::STATE_MORE_MENU,
            'meta' => array_merge($session->meta ?? [], ['more_map' => $map]),
        ]);

        return $this->cml->sayAndCapture(implode(' ', $lines), $locale);
    }

    /**
     * @return array{actions: list<array<string, mixed>>}
     */
    protected function handleMoreMenu(VoiceSession $session, string $digit): array
    {
        $locale = $session->locale;

        if ($digit === '0') {
            return $this->repeatMenu($session);
        }

        $map = $session->meta['more_map'] ?? [];
        $intent = $map[$digit] ?? null;

        if ($intent === null) {
            return $this->startMoreMenu($session);
        }

        return $this->runIntent($session, $intent);
    }

    /**
     * Single funnel for every answer so the PIN gate cannot be bypassed.
     *
     * @return array{actions: list<array<string, mixed>>}
     */
    protected function runIntent(VoiceSession $session, string $intent): array
    {
        $locale = $session->locale;

        if ($this->pins->mustVerify($session, $intent)) {
            return $this->startPinEntry($session, $intent);
        }

        $institutionId = (int) $session->institution_id;

        if ($intent === 'notice') {
            return $this->answerThenMenu($session, $this->answers->latestNotice($institutionId, $locale));
        }

        if ($intent === 'contact') {
            return $this->answerThenMenu($session, $this->answers->schoolContact($institutionId, $locale));
        }

        if ($intent === 'ai') {
            return $this->startAiQuestion($session);
        }

        if ($intent === 'agent') {
            return $this->startTransfer($session);
        }

        $student = $session->student_id ? Student::find($session->student_id) : null;
        if (! $student) {
            return $this->startChildSelect($session);
        }

        return match ($intent) {
            'fees' => $this->answerThenMenu($session, $this->answers->feeBalance($student, $institutionId, $locale)),
            'fee_details' => $this->answerThenMenu($session, $this->answers->feeDetails($student, $institutionId, $locale)),
            'attendance' => $this->answerThenMenu($session, $this->answers->todayAttendance($student, $locale)),
            'ptm' => $this->answerThenMenu($session, $this->answers->latestPtm($student, $institutionId, $locale)),
            'pickup' => $this->answerThenMenu($session, $this->answers->pickupStatus($student, $institutionId, $locale)),
            'requests' => $this->answerThenMenu($session, $this->answers->requestStatus($student, $institutionId, $locale)),
            default => $this->repeatMenu($session, __('voice.invalid', [], $locale)),
        };
    }

    /**
     * @return array{actions: list<array<string, mixed>>}
     */
    protected function startPinEntry(VoiceSession $session, string $intent): array
    {
        $locale = $session->locale;

        if (! $this->pins->hasPin($session)) {
            return $this->answerThenMenu($session, __('voice.pin_not_set', [], $locale));
        }

        $record = $this->pins->pinFor($session);
        if ($record && $record->isLocked()) {
            $this->sessions->end($session, 'pin_locked');

            return $this->cml->sayAndHangup(__('voice.pin_locked', [], $locale), $locale);
        }

        $session->update([
            'state' => VoiceSession::STATE_PIN_ENTRY,
            'meta' => array_merge($session->meta ?? [], ['pending_intent' => $intent]),
        ]);

        return $this->cml->sayAndCaptureDigits(__('voice.pin_prompt', [], $locale), $locale, 4, '#');
    }

    /**
     * @return array{actions: list<array<string, mixed>>}
     */
    protected function handlePinEntry(VoiceSession $session, string $digits): array
    {
        $locale = $session->locale;
        $intent = $session->meta['pending_intent'] ?? null;

        $result = $this->pins->verify($session, $digits);
        $session = $session->fresh();

        return match ($result['status']) {
            'ok' => $intent
                ? $this->runIntent($session, $intent)
                : $this->repeatMenu($session, __('voice.pin_ok', [], $locale)),
            'missing' => $this->answerThenMenu($session, __('voice.pin_not_set', [], $locale)),
            'locked', 'exhausted' => $this->endWith($session, 'pin_locked', __('voice.pin_locked', [], $locale)),
            default => $this->cml->sayAndCaptureDigits(
                __('voice.pin_invalid', [], $locale) . ' ' . __('voice.pin_prompt', [], $locale),
                $locale,
                4,
                '#'
            ),
        };
    }

    /**
     * @return array{actions: list<array<string, mixed>>}
     */
    protected function startAiQuestion(VoiceSession $session): array
    {
        $locale = $session->locale;

        if (! $this->agent->isAvailableFor($session)) {
            return $this->answerThenMenu($session, __('voice.ai_unavailable', [], $locale));
        }

        if ($this->agent->questionsExhausted($session)) {
            return $this->answerThenMenu($session, __('voice.ai_limit', [], $locale));
        }

        $session->update(['state' => VoiceSession::STATE_AI_LISTEN]);

        return $this->cml->sayAndRecord(__('voice.ai_prompt', [], $locale), $locale);
    }

    /**
     * @return array{actions: list<array<string, mixed>>}
     */
    protected function startTransfer(VoiceSession $session): array
    {
        $locale = $session->locale;
        $institutionId = (int) $session->institution_id;

        if (! $this->transfers->isEnabled($institutionId)) {
            return $this->answerThenMenu($session, $this->answers->secretaryMessage($institutionId, $locale));
        }

        if (! $this->transfers->isWithinWorkingHours($institutionId)) {
            return $this->answerThenMenu(
                $session,
                __('voice.transfer_closed', [], $locale) . ' ' . $this->answers->secretaryMessage($institutionId, $locale)
            );
        }

        $this->transfers->markTransferred($session);

        return $this->cml->sayAndDial(
            __('voice.transfer_connecting', [], $locale),
            $locale,
            $this->transfers->endpointType($institutionId),
            (string) $this->transfers->identity($institutionId)
        );
    }

    /**
     * @return array{actions: list<array<string, mixed>>}
     */
    protected function startChildSelect(VoiceSession $session): array
    {
        $locale = $session->locale;
        $parent = $session->parent;

        if (! $session->parent_id || ! $parent) {
            return $this->answerThenMenu($session, __('voice.no_children', [], $locale));
        }

        $children = $this->identity->childrenForParent($parent, (int) $session->institution_id);
        if ($children->isEmpty()) {
            return $this->answerThenMenu($session, __('voice.no_children', [], $locale));
        }

        if ($children->count() === 1) {
            $session->update([
                'student_id' => $children->first()->id,
                'state' => VoiceSession::STATE_PARENT_MENU,
            ]);

            return $this->repeatMenu($session->fresh());
        }

        $lines = [__('voice.select_child_intro', [], $locale)];
        foreach ($children->take(9)->values() as $index => $child) {
            $lines[] = __('voice.select_child_option', [
                'digit' => $index + 1,
                'name' => $child->first_name ?: $child->full_name,
            ], $locale);
        }

        $session->update([
            'state' => VoiceSession::STATE_SELECT_CHILD,
            'meta' => array_merge($session->meta ?? [], [
                'child_ids' => $children->take(9)->pluck('id')->values()->all(),
            ]),
        ]);

        return $this->cml->sayAndCapture(implode(' ', $lines), $locale);
    }

    /**
     * @return array{actions: list<array<string, mixed>>}
     */
    protected function handleChildSelect(VoiceSession $session, string $digit): array
    {
        $locale = $session->locale;
        $ids = $session->meta['child_ids'] ?? [];
        $index = ((int) $digit) - 1;

        if (! isset($ids[$index])) {
            return $this->cml->sayAndCapture(
                __('voice.invalid', [], $locale) . ' ' . __('voice.select_child_again', [], $locale),
                $locale
            );
        }

        $session->update([
            'student_id' => (int) $ids[$index],
            'state' => VoiceSession::STATE_PARENT_MENU,
        ]);

        return $this->repeatMenu($session->fresh());
    }

    /**
     * @return array{actions: list<array<string, mixed>>}
     */
    protected function toggleLocale(VoiceSession $session): array
    {
        $next = $session->locale === 'en' ? 'fr' : 'en';
        $session->update(['locale' => $next]);
        app()->setLocale($next);

        return $this->repeatMenu($session->fresh());
    }

    /**
     * @return array{actions: list<array<string, mixed>>}
     */
    protected function repeatMenu(VoiceSession $session, ?string $prefix = null): array
    {
        $session->update([
            'state' => $session->isParentMenu()
                ? VoiceSession::STATE_PARENT_MENU
                : VoiceSession::STATE_GUEST_MENU,
        ]);

        $session = $session->fresh();
        $text = trim(($prefix ? $prefix . ' ' : '') . $this->menuPrompt($session));

        return $this->cml->sayAndCapture($text, $session->locale);
    }

    /**
     * @return array{actions: list<array<string, mixed>>}
     */
    protected function answerThenMenu(VoiceSession $session, string $answer): array
    {
        $session->update(['state' => $session->isParentMenu()
            ? VoiceSession::STATE_PARENT_MENU
            : VoiceSession::STATE_GUEST_MENU]);

        $text = trim($answer . ' ' . __('voice.return_hint', [], $session->locale));

        Log::info('Voice IVR answer', [
            'call_id' => $session->call_id,
            'digit' => $session->last_digit,
            'institution_id' => $session->institution_id,
        ]);

        return $this->cml->sayAndCapture($text, $session->locale);
    }

    /**
     * @return array{actions: list<array<string, mixed>>}
     */
    protected function endWith(VoiceSession $session, string $reason, string $message): array
    {
        $this->sessions->end($session, $reason);

        return $this->cml->sayAndHangup($message, $session->locale);
    }
}
