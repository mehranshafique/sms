<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\MessageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class MessageLogController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (!$user || (!$user->hasRole('Super Admin') && !$user->hasRole('School Admin'))) {
                abort(403, 'Unauthorized access to message logs.');
            }
            return $next($request);
        });

        $this->setPageTitle(__('message_log.page_title'));
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuper = $user->hasRole('Super Admin');
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            $query = MessageLog::query()->with('institution:id,name,code')->latest('created_at');

            if ($isSuper) {
                if ($request->filled('institution_id')) {
                    $query->where('institution_id', $request->institution_id);
                } elseif ($institutionId && $institutionId !== 'global') {
                    $query->where('institution_id', $institutionId);
                }
            } else {
                $query->where('institution_id', $institutionId);
            }

            if ($request->filled('channel')) {
                $query->where('channel', $request->channel);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('event_key')) {
                $query->where('event_key', $request->event_key);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('created_at', fn ($row) => optional($row->created_at)->format('Y-m-d H:i:s'))
                ->addColumn('institution_name', function ($row) {
                    return $row->institution?->code ?? ($row->institution_id ? '#' . $row->institution_id : '—');
                })
                ->editColumn('channel', function ($row) {
                    $badge = $row->channel === 'whatsapp' ? 'success' : 'primary';
                    return '<span class="badge badge-' . $badge . ' light">' . e(strtoupper($row->channel)) . '</span>';
                })
                ->editColumn('status', function ($row) {
                    $map = ['sent' => 'success', 'failed' => 'danger', 'skipped' => 'secondary'];
                    $badge = $map[$row->status] ?? 'secondary';
                    return '<span class="badge badge-' . $badge . ' light">' . e(__('message_log.status_' . $row->status)) . '</span>';
                })
                ->editColumn('credited', fn ($row) => $row->credited ? '✓' : '—')
                ->editColumn('error', fn ($row) => $row->error ? e($row->error) : '—')
                ->rawColumns(['channel', 'status', 'error'])
                ->make(true);
        }

        $institutions = $isSuper
            ? Institution::orderBy('name')->get(['id', 'name', 'code'])
            : collect();

        $eventKeys = MessageLog::query()
            ->when(!$isSuper, fn ($q) => $q->where('institution_id', $institutionId))
            ->when($isSuper && $institutionId && $institutionId !== 'global', fn ($q) => $q->where('institution_id', $institutionId))
            ->whereNotNull('event_key')
            ->distinct()
            ->orderBy('event_key')
            ->pluck('event_key');

        $statsQuery = MessageLog::query()
            ->when(!$isSuper, fn ($q) => $q->where('institution_id', $institutionId))
            ->when($isSuper && $institutionId && $institutionId !== 'global', fn ($q) => $q->where('institution_id', $institutionId));

        $stats = [
            'sent' => (clone $statsQuery)->where('status', 'sent')->where('created_at', '>=', now()->subDays(7))->count(),
            'failed' => (clone $statsQuery)->where('status', 'failed')->where('created_at', '>=', now()->subDays(7))->count(),
            'sms' => (clone $statsQuery)->where('channel', 'sms')->where('created_at', '>=', now()->subDays(7))->count(),
            'whatsapp' => (clone $statsQuery)->where('channel', 'whatsapp')->where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return view('message_logs.index', compact('institutions', 'eventKeys', 'stats', 'isSuper'));
    }
}
