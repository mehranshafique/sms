<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Institute;
use App\Models\Student;
use App\Models\Staff;
use App\Models\Campus;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->setPageTitle(__('dashboard.page_title'));

        // 1. Core Counts
        $totalStudents = Student::count();
        $totalStaff = Staff::count();
        $totalCampuses = Campus::count();
        $totalInstitutes = Institute::count();

        // 2. Recent Students (Limit 5 for display)
        $recentStudents = Student::with('institute')
            ->latest()
            ->take(5)
            ->get();

        // 3. Current Academic Session
        // Assuming there is one global current session or per institute (taking first found for dashboard overview)
        $currentSession = AcademicSession::where('is_current', true)->first();

        // 4. Chart Data: Student Registrations (Last 7 Days)
        $startDate = Carbon::now()->subDays(6);
        $endDate = Carbon::now();

        $chartData = Student::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        // Fill in missing dates with 0
        $chartLabels = [];
        $chartValues = [];
        
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $formattedDate = $date->format('Y-m-d');
            $displayDate = $date->format('M d'); // e.g. "Oct 25"
            
            $record = $chartData->firstWhere('date', $formattedDate);
            
            $chartLabels[] = $displayDate;
            $chartValues[] = $record ? $record->count : 0;
        }

        return view('dashboard.dashboard', compact(
            'totalStudents', 
            'totalStaff', 
            'totalCampuses', 
            'totalInstitutes', 
            'recentStudents', 
            'chartLabels', 
            'chartValues',
            'currentSession'
        ));
    }
}