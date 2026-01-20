<?php

return [
    'page_title' => 'Grade Levels',
    'messages' => [
        'success_create' => 'Grade Level created successfully.',
        'success_update' => 'Grade Level updated successfully.',
        'success_delete' => 'Grade Level deleted successfully.',
        'error_occurred' => 'Error Occurred',
        'cannot_delete_linked_data' => 'Cannot delete this grade level because it is linked to other data.',
        'duplicate_entry' => 'A grade level with this name already exists in this education cycle.',
    ],
    
    // Stats
    'grade_management' => 'Grade Management',
    'manage_list_subtitle' => 'Manage academic levels and cycles',
    'total_grades' => 'Total Grades',
    'primary_cycle' => 'Primary Cycle',
    'secondary_cycle' => 'Secondary Cycle',
    'university_cycle' => 'University Cycle',

    // Table
    'grade_list' => 'Grade Level List',
    'table_no' => '#',
    'name' => 'Grade Name',
    'code' => 'Code',
    'order' => 'Order Index',
    'cycle' => 'Cycle',
    'action' => 'Action',
    'search_placeholder' => 'Search Grades...',
    'no_records_found' => 'No records found',

    // Form
    'create_new' => 'Create New',
    'add_new_grade' => 'Add New Grade Level',
    'edit_grade' => 'Edit Grade Level',
    'basic_information' => 'Basic Information',
    'grade_name' => 'Grade Name',
    'grade_code' => 'Grade Code',
    'order_index' => 'Order Index',
    'education_cycle' => 'Education Cycle',
    
    // Dropdown / Values (Used in _form and controller display)
    'institution' => 'Institution',
    'select_institution' => 'Select Institution',
    'select_cycle' => 'Select Cycle',
    'primary' => 'Primary',
    'secondary' => 'Secondary',
    'university' => 'University',
    'vocational' => 'Vocational',

    // Cycle Keys for Enum (Matches AcademicType.php)
    'cycle_primary' => 'Primary',
    'cycle_secondary' => 'Secondary',
    'cycle_lmd' => 'University',
    'cycle_vocational' => 'Vocational',

    // Buttons
    'save' => 'Save Grade',
    'update' => 'Update Grade',
    'export' => 'Export',
    'bulk_delete' => 'Bulk Delete',
    'cancel' => 'Cancel',

    // Validation/Alerts
    'enter_name' => 'e.g. Grade 1',
    'enter_code' => 'e.g. G1',
    'auto_code_help' => 'Leave empty to auto-generate from Grade Name.', // Added
    'cycle_hint' => 'Select the education cycle for this grade.',
    'order_hint' => 'Used for sorting grades (e.g. 1, 2, 3).',
    'are_you_sure' => 'Are you sure?',
    'delete_warning' => 'This cannot be undone.',
    'yes_delete' => 'Yes, delete!',
    'yes_bulk_delete' => 'Yes, delete selected!',
    'success' => 'Success!',
    'error_occurred' => 'Error Occurred',
    'something_went_wrong' => 'Something went wrong!',
    'validation_error' => 'Validation Error',
    'is_required' => 'is required',
];