<?php

return [
    'title' => 'Exam Scheduling',
    'manage_title' => 'Manage Date Sheet',
    'subtitle' => 'Schedule dates, times, and rooms for class exams',
    
    // Filters
    'select_exam' => 'Select Exam',
    'select_class' => 'Select Class',
    'load_subjects' => 'Load Subjects',
    'download_admit_card' => 'Download Admit Cards',
    'auto_fill' => 'Auto Fill Schedule', // Added
    
    // Table Headers
    'subject' => 'Subject',
    'date' => 'Exam Date',
    'time' => 'Time',
    'start_time' => 'Start Time',
    'end_time' => 'End Time',
    'room' => 'Room Number',
    'max_marks' => 'Max Marks',
    'pass_marks' => 'Pass Marks',
    'is_scheduled' => 'Scheduled?',
    'invigilator_sign' => 'Invigilator Sign',
    
    // Admit Card
    'admit_card' => 'Admit Card / Roll No Slip',
    'instructions' => 'Important Instructions',
    'instruction_1' => 'Students must bring this admit card to the examination hall.',
    'instruction_2' => 'Electronic devices (mobiles, smartwatches) are strictly prohibited.',
    'instruction_3' => 'Please arrive at least 15 minutes before the scheduled time.',
    'student_sign' => 'Student Signature',
    'controller_sign' => 'Controller of Exams',
    'principal_sign' => 'Principal Signature',
    'generated_on' => 'Generated on',
    'no_schedules_found' => 'No exam schedule found for this class.',
    
    // Actions
    'save_schedule' => 'Save Date Sheet',
    'clear' => 'Clear',
    
    // Messages
    'success_saved' => 'Exam schedule saved successfully.',
    'auto_fill_success' => 'Schedule suggestions applied successfully. Please review before saving.', // Added
    'auto_fill_confirm' => 'This will overwrite empty or existing dates in the form with suggested dates based on school timings. Continue?', // Added
    'error_overlap_class' => 'Time conflict detected for this class on :date between :start - :end.',
    'error_overlap_room' => 'Room :room is already booked on :date between :start - :end.',
    'error_date_range' => 'Date :date is outside the exam period (:start to :end).',
    'no_subjects_found' => 'No subjects found for this class.',
    'validation_error' => 'Please check the form for errors.',
    'select_filters' => 'Please select an Exam and Class to proceed.',
];