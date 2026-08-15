<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\Invoice;
use App\Models\InstitutionSetting;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class ReportCardAccessService
{
    /**
     * @return array{
     *   allowed: bool,
     *   blocked: bool,
     *   mode: string,
     *   period_key: ?string,
     *   total_paid: float,
     *   required: float,
     *   remaining: float,
     *   outstanding: float,
     *   message_en: string,
     *   message_fr: string
     * }
     */
    public function check(Student $student, int $institutionId, ?string $periodKey = null, bool $enforcePayment = true): array
    {
        $currency = \App\Enums\CurrencySymbol::default();
        $outstanding = (float) Invoice::where('student_id', $student->id)
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->sum(DB::raw('total_amount - paid_amount'));

        $blockEnabled = (string) InstitutionSetting::get($institutionId, 'block_reports_on_debt', 0) === '1'
            || (bool) InstitutionSetting::get($institutionId, 'block_reports_on_debt', false);

        $sessionId = AcademicSession::where('institution_id', $institutionId)
            ->where('is_current', true)
            ->value('id');

        $paidQuery = Invoice::where('student_id', $student->id);
        if ($sessionId) {
            $paidQuery->where('academic_session_id', $sessionId);
        }
        $totalPaid = (float) $paidQuery->sum('paid_amount');

        $mins = $this->minPaidMap($institutionId);
        $required = $this->requiredForPeriod($mins, $periodKey);

        if (! $blockEnabled) {
            return $this->allowResult($periodKey, $totalPaid, (float) ($required ?? 0), $outstanding);
        }

        $blocked = null;

        if ($required !== null && $required > 0) {
            $remaining = max(0, $required - $totalPaid);
            if ($remaining > 0) {
                $fmtRem = number_format($remaining, 2) . ' ' . $currency;
                $fmtReq = number_format($required, 2) . ' ' . $currency;
                $blocked = [
                    'allowed' => false,
                    'blocked' => true,
                    'mode' => 'min_paid',
                    'period_key' => $periodKey,
                    'total_paid' => $totalPaid,
                    'required' => $required,
                    'remaining' => $remaining,
                    'outstanding' => $outstanding,
                    'message_en' => "⛔ Access denied. You still need to pay {$fmtRem} to reach the minimum required for this report period ({$fmtReq}). Please settle to view results.",
                    'message_fr' => "⛔ Accès refusé. Il vous reste {$fmtRem} à payer pour atteindre le minimum requis pour cette période du bulletin ({$fmtReq}). Veuillez régler pour voir vos résultats.",
                ];
            }
        } elseif ($outstanding > 0) {
            $fmt = number_format($outstanding, 2) . ' ' . $currency;
            $blocked = [
                'allowed' => false,
                'blocked' => true,
                'mode' => 'outstanding',
                'period_key' => $periodKey,
                'total_paid' => $totalPaid,
                'required' => 0,
                'remaining' => $outstanding,
                'outstanding' => $outstanding,
                'message_en' => "⛔ Access denied. You have an outstanding balance of {$fmt}. Please settle to view results.",
                'message_fr' => "⛔ Accès refusé. Vous avez un solde impayé de {$fmt}. Veuillez régler pour voir vos résultats.",
            ];
        }

        if ($blocked && $enforcePayment) {
            return $blocked;
        }

        $allowed = $this->allowResult($periodKey, $totalPaid, (float) ($required ?? 0), $outstanding);
        if ($blocked) {
            $allowed['mode'] = $blocked['mode'];
            $allowed['remaining'] = $blocked['remaining'];
            $allowed['required'] = $blocked['required'];
            $allowed['message_en'] = $blocked['message_en'];
            $allowed['message_fr'] = $blocked['message_fr'];
            $allowed['staff_banner'] = true;
        }

        return $allowed;
    }

    /**
     * @return array<string, float|int|string>
     */
    public function minPaidMap(int $institutionId): array
    {
        $raw = InstitutionSetting::get($institutionId, 'report_min_paid_amounts', '{}');
        $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Resolve configured minimum for a period key, including trimester_N / semester_N aliases.
     *
     * @param  array<string, float|int|string>  $mins
     */
    protected function requiredForPeriod(array $mins, ?string $periodKey): ?float
    {
        if ($periodKey === null || $periodKey === '') {
            return null;
        }

        $candidates = [$periodKey];
        if (preg_match('/^trimester_(\d+)$/', $periodKey, $m)) {
            $candidates[] = 'trimester_exam_' . $m[1];
        } elseif (preg_match('/^trimester_exam_(\d+)$/', $periodKey, $m)) {
            $candidates[] = 'trimester_' . $m[1];
        } elseif (preg_match('/^semester_(\d+)$/', $periodKey, $m)) {
            $candidates[] = 'semester_exam_' . $m[1];
        } elseif (preg_match('/^semester_exam_(\d+)$/', $periodKey, $m)) {
            $candidates[] = 'semester_' . $m[1];
        }

        foreach ($candidates as $key) {
            if (array_key_exists($key, $mins) && $mins[$key] !== '' && $mins[$key] !== null) {
                return (float) $mins[$key];
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function allowResult(?string $periodKey, float $totalPaid, float $required, float $outstanding): array
    {
        return [
            'allowed' => true,
            'blocked' => false,
            'mode' => 'ok',
            'period_key' => $periodKey,
            'total_paid' => $totalPaid,
            'required' => $required,
            'remaining' => 0,
            'outstanding' => $outstanding,
            'message_en' => '',
            'message_fr' => '',
        ];
    }
}
