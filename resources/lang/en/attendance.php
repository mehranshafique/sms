<?php

return [
    'page_title' => 'Student Attendance',
    'attendance_management' => 'Attendance Management',
    'manage_list_subtitle' => 'Track daily student attendance',
    'messages' => [
        'success_marked' => 'Attendance marked successfully.',
    ],
    
    // Index
    'attendance_list' => 'Attendance Log',
    'mark_attendance' => 'Mark Attendance',
    'view_register' => 'View Register', 
    'filter_class' => 'Filter Class',
    'filter_date' => 'Filter Date',
    'export' => 'Export',
    
    // Table
    'table_no' => '#',
    'student' => 'Student Name',
    'roll_no' => 'Roll / ID',
    'class' => 'Class',
    'status' => 'Status',
    'date' => 'Date',
    'search_placeholder' => 'Search records...',
    'no_records_found' => 'No records found',

    // Create / Mark
    'mark_attendance_title' => 'Mark Class Attendance',
    'select_criteria' => 'Select Criteria',
    'select_class' => 'Select Class Section',
    'select_date' => 'Select Date',
    'load_students' => 'Load Students',
    'student_list' => 'Student List',
    
    'present' => 'Present',
    'absent' => 'Absent',
    'late' => 'Late',
    'excused' => 'Excused',
    'half_day' => 'Half Day',
    
    'mark_all_present' => 'Mark All Present',
    'mark_all_absent' => 'Mark All Absent',
    'save_attendance' => 'Save Attendance',
    'update_attendance' => 'Update Attendance',
    'not_enrolled' => 'No active students found in this class.',
    'no_active_session' => 'No active academic session found.',
    
    // Lock Logic
    'attendance_locked' => 'Attendance Locked',
    'attendance_locked_desc' => 'Attendance for this date cannot be modified as it is older than 7 days.',
    'attendance_locked_error' => 'Action Forbidden: Attendance cannot be modified after 7 days.',
    'locked' => 'Locked',

    // Report / Register
    'register_title' => 'Attendance Register',
    'register_subtitle' => 'Monthly class attendance view',
    'back_to_list' => 'Back to List',
    'month' => 'Month',
    'year' => 'Year',
    'print' => 'Print Register', // Updated key
    'no_students_found_class' => 'No students found for this class.',
    
    // Print View Specific
    'attendance_report' => 'Attendance Report',
    'class_details' => 'Class Details',
    'total_students' => 'Total Students',
    'generated_on' => 'Generated On',
    'legend' => 'Legend',
    'legend_p' => 'P: Present',
    'legend_a' => 'A: Absent',
    'legend_l' => 'L: Late',
    'legend_e' => 'E: Excused',
    'legend_h' => 'H: Half Day',
    'summary' => 'Summary', // Added Summary

    // Admin Settings Errors
    'admin_blocked' => 'Attendance marking is currently blocked by administrator.',
    'admin_blocked_error' => 'Action Blocked: Attendance marking is disabled.',
    'grace_period_exceeded' => 'Modification allowed only within :days days.',
    'grace_period_error' => 'Action Blocked: Cannot modify attendance older than :days days.',

    'error_occurred' => 'Error Occurred',
    'validation_error' => 'Validation Error',
    'success' => 'Success!',
];