<?php

namespace App\Services;

use App\Models\InAppNotification;

/**
 * Maps unread in-app notifications to sidebar menu keys for badge counts.
 */
class SidebarMenuBadgeService
{
    /** Parent menu key => child menu keys (totals are summed). */
    public const GROUPS = [
        'fees_collection' => ['payment-proofs', 'invoices'],
        'students' => ['requests', 'pickups'],
        'staff' => ['staff-leaves'],
        'budget_payroll' => ['fund-requests'],
        'examinations' => ['exams'],
        'communication' => ['notices'],
    ];

    /** URL path fragment => menu key (first match wins; order matters). */
    protected array $linkPatterns = [
        'payment-proofs' => 'payment-proofs',
        'budgets/requests' => 'fund-requests',
        'staff-leaves' => 'staff-leaves',
        'pickups' => 'pickups',
        'student/notices' => 'notices',
        'notices' => 'notices',
        'marks/my_marks' => 'exams',
        '/exams' => 'exams',
        'requests' => 'requests',
        'invoices' => 'invoices',
        'payments' => 'invoices',
    ];

    protected array $typeToMenu = [
        'payment_proof_submitted' => 'payment-proofs',
        'payment_proof_rejected' => 'payment-proofs',
        'payment_proof' => 'payment-proofs',
        'invoice' => 'invoices',
        'payment' => 'invoices',
        'student_request_new' => 'requests',
        'student_request' => 'requests',
        'staff_leave_new' => 'staff-leaves',
        'staff_leave' => 'staff-leaves',
        'fund_request_new' => 'fund-requests',
        'fund_request' => 'fund-requests',
        'pickup_scan' => 'pickups',
        'pickup' => 'pickups',
        'notice' => 'notices',
        'exam' => 'exams',
    ];

    /** @return array<string, int> menu_key => unread count */
    public function countsForUser(int $userId): array
    {
        $notifications = InAppNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->get(['type', 'link', 'meta']);

        $counts = [];

        foreach ($notifications as $notification) {
            $key = $this->resolveMenuKey($notification);
            if ($key) {
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }

        foreach (self::GROUPS as $group => $children) {
            $total = 0;
            foreach ($children as $child) {
                $total += $counts[$child] ?? 0;
            }
            if ($total > 0) {
                $counts[$group] = $total;
            }
        }

        return $counts;
    }

    protected function resolveMenuKey(InAppNotification $notification): ?string
    {
        $meta = $notification->meta;
        if (is_array($meta) && !empty($meta['menu_key'])) {
            return (string) $meta['menu_key'];
        }

        $link = strtolower($notification->link ?? '');
        foreach ($this->linkPatterns as $fragment => $key) {
            if (str_contains($link, $fragment)) {
                return $key;
            }
        }

        return $this->typeToMenu[$notification->type] ?? null;
    }
}
