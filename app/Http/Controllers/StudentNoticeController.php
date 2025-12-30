<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentNoticeController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        
        // Find Student Profile linked to User
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            // Handle case where user is logged in but no student profile exists
            return view('students.notices.error', ['message' => __('notice.student_profile_not_found')]);
        }

        // Fetch Notices
        // Logic: 
        // 1. Institution ID matches Student's Institute OR is Null (System Wide)
        // 2. Audience is 'all' OR 'student'
        // 3. Is Published
        
        $notices = Notice::where(function($q) use ($student) {
                $q->where('institution_id', $student->institution_id)
                  ->orWhereNull('institution_id');
            })
            ->whereIn('audience', ['all', 'student'])
            ->where('is_published', true)
            // FIX: Removed 'publish_date' filter as the column does not exist.
            // Using standard 'latest()' which sorts by 'created_at' desc.
            ->latest() 
            ->paginate(10);

        return view('students.notices.index', compact('notices'));
    }

    public function show(Notice $notice)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->id)->firstOrFail();

        // Security Check: Ensure student is allowed to see this specific notice
        // 1. Check Institution Scope
        if ($notice->institution_id && $notice->institution_id !== $student->institution_id) {
            abort(403, __('notice.unauthorized'));
        }

        // 2. Check Audience Scope
        if (!in_array($notice->audience, ['all', 'student'])) {
            abort(403, __('notice.unauthorized'));
        }

        return view('students.notices.show', compact('notice'));
    }
}