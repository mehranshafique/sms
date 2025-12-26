<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class NoticeController extends BaseController
{
    public function __construct()
    {
        $this->authorizeResource(Notice::class, 'notice');
        $this->setPageTitle(__('notice.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            $query = Notice::select('notices.*')->with('creator');

            // Scoping: Show Global Notices + Institution Specific Notices
            if ($institutionId) {
                $query->where(function($q) use ($institutionId) {
                    $q->where('institution_id', $institutionId)
                      ->orWhereNull('institution_id');
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('type', function($row){
                    $badges = [
                        'info' => 'badge-info', 
                        'warning' => 'badge-warning', 
                        'urgent' => 'badge-danger'
                    ];
                    $class = $badges[$row->type] ?? 'badge-secondary';
                    return '<span class="badge '.$class.'">'.ucfirst($row->type).'</span>';
                })
                ->editColumn('audience', function($row){
                    return ucfirst($row->audience);
                })
                ->editColumn('status', function($row){
                    return $row->is_published 
                        ? '<span class="badge badge-success">'.__('notice.published').'</span>' 
                        : '<span class="badge badge-light">'.__('notice.draft').'</span>';
                })
                ->editColumn('published_at', function($row){
                    return $row->published_at ? $row->published_at->format('Y-m-d H:i') : '-';
                })
                ->addColumn('action', function($row){
                    $btn = '<div class="d-flex justify-content-end action-buttons">';
                    
                    if(auth()->user()->can('update', $row)){
                        $btn .= '<a href="'.route('notices.edit', $row->id).'" class="btn btn-primary shadow btn-xs sharp me-1"><i class="fa fa-pencil"></i></a>';
                    }
                    if(auth()->user()->can('delete', $row)){
                        $btn .= '<button type="button" class="btn btn-danger shadow btn-xs sharp delete-btn" data-id="'.$row->id.'"><i class="fa fa-trash"></i></button>';
                    }
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['type', 'status', 'action'])
                ->make(true);
        }

        $totalNotices = Notice::count();
        $activeNotices = Notice::where('is_published', true)->count();

        return view('notices.index', compact('totalNotices', 'activeNotices'));
    }

    public function create()
    {
        return view('notices.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:info,warning,urgent',
            'audience' => 'required|in:all,staff,student,parent',
            'published_at' => 'nullable|date',
        ]);

        $notice = new Notice($validated);
        $notice->created_by = Auth::id();
        $notice->institution_id = $this->getInstitutionId(); 
        
        if ($request->has('publish_now') || $request->filled('published_at')) {
            $notice->is_published = true;
            $notice->published_at = $request->published_at ?? now();
        } else {
            $notice->is_published = false;
        }

        $notice->save();

        return response()->json(['message' => __('notice.success_create'), 'redirect' => route('notices.index')]);
    }

    public function edit(Notice $notice)
    {
        return view('notices.edit', compact('notice'));
    }

    public function update(Request $request, Notice $notice)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:info,warning,urgent',
            'audience' => 'required|in:all,staff,student,parent',
            'is_published' => 'boolean'
        ]);

        $notice->update($validated);

        return response()->json(['message' => __('notice.success_update'), 'redirect' => route('notices.index')]);
    }

    public function destroy(Notice $notice)
    {
        $notice->delete();
        return response()->json(['message' => __('notice.success_delete')]);
    }
}