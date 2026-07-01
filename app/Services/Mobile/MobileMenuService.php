<?php

namespace App\Services\Mobile;

use App\Models\User;

class MobileMenuService
{
    /** @return array<string, list<array<string, mixed>>> */
    public function build(User $user, array $capabilities, array $enabledModules): array
    {
        $hasModule = fn (string $slug): bool => in_array($slug, $enabledModules, true);
        $isSuper = !empty($capabilities['super_admin']);
        $gateMode = !empty($capabilities['gate_mode']);

        $staffTools = array_values(array_filter([
            $this->tile('gate_attendance', 'Gate Attendance', 'NFC In / Out', 'nfc', $capabilities['hardware_scan'] ?? false, '/gate-attendance'),
            $this->tile('staff_checkin', 'Staff Check-in', 'Gate staff NFC', 'badge', ($capabilities['staff_gate_attendance'] ?? false) && ($hasModule('staff_attendance') || $isSuper), '/staff-checkin'),
            $this->tile('pickup', 'Kids Pickup', 'QR & NFC Release', 'qr_code_scanner', $capabilities['pickup_management'] ?? false, '/pickup'),
            $this->tile('fee_status', 'Fee Status', 'Check Balances', 'payments', $capabilities['nfc_fee_check'] ?? false, '/fee-status'),
            $this->tile('fee_search', 'Fee Search', 'Lookup by name', 'search', ($capabilities['fee_lookup'] ?? false) && ($hasModule('invoices') || $isSuper), '/fee-search'),
            $this->tile('report_cards', 'Report Cards', 'Parent Signature', 'description', $capabilities['nfc_report_card'] ?? false, '/report-cards'),
            $this->tile('student_identity', 'Student Identity', 'Verify card & photo', 'verified_user', $capabilities['nfc_identity_check'] ?? false, '/student-identity'),
            $this->tile('take_attendance', 'Take Attendance', 'Class / subject', 'fact_check', ($capabilities['mark_attendance'] ?? false) && ($hasModule('student_attendance') || $isSuper), '/take-attendance'),
            $this->tile('class_absentees', 'Class Absentees', "Today's absent list", 'person_off', ($capabilities['teacher_tools'] ?? false) && ($hasModule('student_attendance') || $isSuper), '/class-absentees'),
            $this->tile('timetable', "Today's Timetable", 'Your schedule', 'schedule', ($capabilities['teacher_tools'] ?? false) && ($hasModule('timetables') || $isSuper), '/timetable'),
            $this->tile('today_scans', "Today's Scans", 'Live roster', 'list_alt', $capabilities['hardware_scan'] ?? false, '/today-scans'),
            $this->tile('notices_staff', 'Notices', 'Announcements', 'campaign', ($capabilities['teacher_tools'] ?? false) && ($hasModule('notices') || $hasModule('communication') || $isSuper), '/notices'),
            $this->tile('support', 'Help & Support', 'Open a ticket', 'support_agent', true, '/support'),
        ], fn ($t) => $t['enabled']));

        $studentPortal = array_values(array_filter([
            $this->tile('gate_pass', 'My Gate Pass', 'Generate QR Code', 'qr_code', ($capabilities['student_portal'] ?? false) && ($hasModule('students') || $isSuper), '/gate-pass'),
            $this->tile('my_attendance', 'My Attendance', 'View History', 'event_available', ($capabilities['student_portal'] ?? false) && ($hasModule('student_attendance') || $isSuper), '/my-attendance'),
            $this->tile('my_fees', 'My Fees', 'View Invoices', 'receipt_long', ($capabilities['student_portal'] ?? false) && ($hasModule('invoices') || $isSuper), '/my-fees'),
            $this->tile('my_results', 'My Results', 'View Report Cards', 'school', ($capabilities['student_portal'] ?? false) && ($hasModule('exams') || $hasModule('exam_marks') || $isSuper), '/my-results'),
            $this->tile('my_homework', 'My Homework', 'View Assignments', 'assignment', ($capabilities['student_portal'] ?? false) && ($hasModule('assignments') || $isSuper), '/my-homework'),
            $this->tile('my_requests', 'My Requests', 'Submit Leave/Request', 'mail', ($capabilities['student_portal'] ?? false) && ($hasModule('student_requests') || $isSuper), '/my-requests'),
            $this->tile('timetable_student', "Today's Timetable", 'Your schedule', 'schedule', ($capabilities['student_portal'] ?? false) && ($hasModule('timetables') || $isSuper), '/timetable'),
            $this->tile('notices_student', 'Notices', 'Announcements', 'campaign', ($capabilities['student_portal'] ?? false) && ($hasModule('notices') || $hasModule('communication') || $isSuper), '/notices'),
        ], fn ($t) => $t['enabled']));

        if ($gateMode) {
            $byId = collect($staffTools)->keyBy('id');

            return [
                'layout' => 'gate_terminal',
                'gate_terminal' => array_values(array_filter([
                    $byId->get('gate_attendance'),
                    $byId->get('staff_checkin'),
                    $byId->get('pickup'),
                    $byId->get('fee_status'),
                    $byId->get('report_cards'),
                    $byId->get('student_identity'),
                    $this->tile('live_pickups', 'Live Pickups', 'Pending releases', 'family_restroom', $capabilities['pickup_management'] ?? false, '/pickup/live'),
                    $byId->get('today_scans'),
                ])),
                'staff_tools' => [],
                'student_portal' => [],
            ];
        }

        return [
            'layout' => 'standard',
            'gate_terminal' => [],
            'staff_tools' => $staffTools,
            'student_portal' => $studentPortal,
        ];
    }

  /** @return array<string, mixed> */
    private function tile(string $id, string $title, string $subtitle, string $icon, bool $enabled, string $route): array
    {
        return [
            'id' => $id,
            'title' => $title,
            'subtitle' => $subtitle,
            'icon' => $icon,
            'enabled' => $enabled,
            'route' => $route,
        ];
    }
}
