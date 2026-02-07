<?php

namespace App\Http\Controllers;

use App\Models\SmsTemplate;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;

class SmsTemplateController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('sms_template.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            // Fetch Global Templates OR Institution Specific Overrides
            // Logic: We show all global templates. If the institution has overridden one, we show that instead.
            // Simplified: Just show global for now, allow override on edit.
            
            $data = SmsTemplate::whereNull('institution_id')->get(); // Get Defaults

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('is_active', function($row){
                    return $row->is_active 
                        ? '<span class="badge badge-success">'.__('sms_template.active').'</span>' 
                        : '<span class="badge badge-danger">'.__('sms_template.inactive').'</span>';
                })
                ->addColumn('action', function($row){
                    return '<button class="btn btn-primary btn-xs edit-template shadow" 
                        data-id="'.$row->id.'" 
                        data-key="'.$row->event_key.'"
                        data-name="'.$row->name.'"
                        data-body="'.$row->body.'"
                        data-tags="'.$row->available_tags.'">
                        <i class="fa fa-pencil"></i> '.__('sms_template.edit').'</button>';
                })
                ->rawColumns(['is_active', 'action'])
                ->make(true);
        }

        return view('settings.sms_templates.index');
    }

    public function override(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        $request->validate([
            'event_key' => 'required|string',
            'body' => 'required|string',
        ]);

        // Save as an institution-specific template
        SmsTemplate::updateOrCreate(
            ['institution_id' => $institutionId, 'event_key' => $request->event_key],
            [
                'name' => $request->name, // Inherit or update name
                'body' => $request->body,
                'available_tags' => $request->available_tags,
                'is_active' => $request->has('is_active') ? 1 : 0
            ]
        );

        return response()->json(['message' => __('sms_template.success_override')]);
    }
}