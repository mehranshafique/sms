<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AcademicSessionController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->setPageTitle(__('academic_session.page_title'));
    }

    public function index()
    {
        authorize('academic_session.view');
        $sessions = AcademicSession::where('institution_id', institute()->id)
            ->orderBy('start_year', 'desc')
            ->get();

        return view('academic_sessions.index', compact('sessions'));
    }

    public function store(Request $request)
    {
        authorize('academic_session.create');
        $request->validate([
            'name'        => ['required', Rule::unique('academic_sessions', 'name')->where('institution_id', institute()->id)],
            'start_year'  => 'required|integer|min:2000|max:3000',
            'end_year'    => 'required|integer|gt:start_year',
            'status'      => 'required|in:planned,active,closed',
            'is_current'  => 'nullable|boolean',
        ]);

        // Ensure only one current session
        if ($request->is_current) {
            AcademicSession::where('institution_id', institute()->id)
                ->update(['is_current' => false]);
        }

        AcademicSession::create([
            'institution_id' => institute()->id,
            'name'           => $request->name,
            'start_year'     => $request->start_year,
            'end_year'       => $request->end_year,
            'status'         => $request->status,
            'is_current'     => $request->is_current ?? false,
        ]);

        return redirect()->back()->with('success', __('academic_session.success_create'));
    }

    public function edit(AcademicSession $academic_session)
    {
        authorize('academic_session.edit');
        return response()->json($academic_session);
    }

    public function update(Request $request, AcademicSession $academic_session)
    {
        authorize('academic_session.edit');
        $request->validate([
            'name'        => ['required', Rule::unique('academic_sessions', 'name')
                ->ignore($academic_session->id)
                ->where('institution_id', institute()->id)],
            'start_year'  => 'required|integer|min:2000|max:3000',
            'end_year'    => 'required|integer|gt:start_year',
            'status'      => 'required|in:planned,active,closed',
            'is_current'  => 'nullable|boolean',
        ]);

        if ($request->is_current) {
            AcademicSession::where('institution_id', institute()->id)
                ->update(['is_current' => false]);
        }

        $academic_session->update([
            'name'          => $request->name,
            'start_year'    => $request->start_year,
            'end_year'      => $request->end_year,
            'status'        => $request->status,
            'is_current'    => $request->is_current ?? false,
        ]);

        return redirect()->back()->with('success', __('academic_session.success_update'));
    }

    public function destroy(AcademicSession $academic_session)
    {
        authorize('academic_session.delete');
        $academic_session->delete();
        return redirect()->back()->with('success', __('academic_session.success_delete'));
    }
}
