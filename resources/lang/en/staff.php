<?php

return [

    'page_title' => 'Staff Management',
    'create_page_title' => 'Add Staff',
    'edit_page_title' => 'Edit Staff',
    'messages' => [
        'success_create' => 'Staff created successfully.',
        'success_update' => 'Staff updated successfully.',
        'success_delete' => 'Staff deleted successfully.',
        'error_create' => 'An error occurred while creating staff. Please try again or contact support.',
        'error_update' => 'An error occurred while updating staff. Please try again or contact support.',
    ],
    'index' => [
        'title' => 'Staff',
        'add' => 'Add Staff',

        'table' => [
            'serial' => '#',
            'employee_no' => 'Employee No',
            'user' => 'User',
            'campus' => 'Campus',
            'designation' => 'Designation',
            'department' => 'Department',
            'hire_date' => 'Hire Date',
            'status' => 'Status',
            'action' => 'Action',
        ],

        'confirm_delete_title' => 'Are you sure?',
        'confirm_delete_button' => 'Yes, delete it!',
    ],

    'create' => [
        'title' => 'Add Staff',
        'subtitle' => 'Fill in the details to register a new staff member',

        'sections' => [
            'user_details' => 'User Details',
            'staff_details' => 'Staff Details',
        ],

        'fields' => [
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'password' => 'Password',
            'role' => 'Role',
            'address' => 'Address',
            'designation' => 'Designation',
            'department' => 'Department',
            'hire_date' => 'Hire Date',
            'status' => 'Status',
        ],

        'placeholders' => [
            'name' => 'Full Name',
            'email' => 'Email',
            'phone' => 'Phone (optional)',
            'password' => 'Password',
            'address' => 'Address',
            'designation' => 'Designation',
            'department' => 'Department',
            'hire_date' => 'YYYY-MM-DD',
        ],

        'status_options' => [
            'active' => 'Active',
            'on_leave' => 'On Leave',
            'terminated' => 'Terminated',
        ],

        'buttons' => [
            'save' => 'Save Staff',
        ],
    ],

    'edit' => [
        'title' => 'Edit Staff',
        'subtitle' => 'Update the staff member details',

        'section_user' => 'User Details',
        'section_staff' => 'Staff Details',

        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'password' => 'Password',
        'password_placeholder' => 'Password (leave blank to keep current)',
        'role' => 'Role',
        'address' => 'Address',

        'designation' => 'Designation',
        'department' => 'Department',
        'hire_date' => 'Hire Date',
        'hire_date_placeholder' => 'YYYY-MM-DD',
        'status' => 'Status',

        'update_btn' => 'Update Staff',

        'status_active' => 'Active',
        'status_on_leave' => 'On Leave',
        'status_terminated' => 'Terminated',
    ],


];
