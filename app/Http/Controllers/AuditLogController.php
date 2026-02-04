<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditLog;
use App\Services\AuditLogger;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        // Strict Role Check: Only Super Admin can view logs
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->hasRole('Super Admin')) {
                abort(403, 'Unauthorized access to Audit Logs.');
            }
            return $next($request);
        });
        
        $this->setPageTitle(__('audit.page_title'));
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = AuditLog::with(['user', 'institution'])->select('audit_logs.*')->latest();

            // Filters
            if ($request->filled('module')) {
                $query->where('module', $request->module);
            }
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('created_at', function($row){
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('user_name', function($row){
                    return $row->user ? $row->user->name . ' (' . $row->user->getRoleNames()->first() . ')' : 'System/Guest';
                })
                ->addColumn('institution_name', function($row){
                    return $row->institution ? $row->institution->code : 'Global';
                })
                // ENRICHED IP COLUMN with Location
                ->editColumn('ip_address', function($row){
                    $ip = $row->ip_address;
                    if (!empty($row->location_details)) {
                        $loc = $row->location_details;
                        $details = collect([
                            $loc['city'] ?? null,
                            $loc['region'] ?? null,
                            $loc['country'] ?? null
                        ])->filter()->join(', ');

                        if ($details) {
                            return '<div>' . $ip . '</div><small class="text-muted"><i class="fa fa-map-marker me-1"></i>' . $details . '</small>';
                        }
                    }
                    return $ip;
                })
                ->editColumn('action', function($row){
                    $colors = [
                        'Login' => 'success',
                        'Logout' => 'warning',
                        'Create' => 'primary',
                        'Update' => 'info',
                        'Delete' => 'danger',
                        'Export' => 'secondary'
                    ];
                    // Simple logic to pick color based on first word
                    $firstWord = explode(' ', $row->action)[0];
                    $color = $colors[$firstWord] ?? 'dark';
                    
                    return '<span class="badge badge-'.$color.'">'.$row->action.'</span>';
                })
                ->addColumn('details', function($row){
                    // Truncate description for table view
                    return '<span title="'.$row->description.'">'.\Illuminate\Support\Str::limit($row->description, 50).'</span>';
                })
                ->rawColumns(['action', 'details', 'ip_address']) // added ip_address to raw
                ->make(true);
        }

        // Get unique modules for filter dropdown
        $modules = AuditLog::distinct('module')->pluck('module');

        return view('tracking.audit_log.index', compact('modules'));
    }
}