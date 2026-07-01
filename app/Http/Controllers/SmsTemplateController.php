<?php

namespace App\Http\Controllers;

use App\Models\SmsTemplate;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Middleware\PermissionMiddleware;

class SmsTemplateController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(PermissionMiddleware::class . ':sms_template.view')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':sms_template.update')->only(['update', 'override']);
        $this->setPageTitle(__('sms_template.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            // 1. Fetch Global Defaults and index them by their event key
            $globalTemplates = SmsTemplate::whereNull('institution_id')->get()->keyBy('event_key');
            
            if ($institutionId) {
                // 2. Fetch Institution Specific Overrides and index them by their event key
                $institutionTemplates = SmsTemplate::where('institution_id', $institutionId)->get()->keyBy('event_key');
                
                // 3. Merge: Use array_merge to force overwriting by the string 'event_key'.
                // (Eloquent's native merge() uses 'id', which causes the duplicates you saw).
                $data = collect(array_merge($globalTemplates->all(), $institutionTemplates->all()))->values();
            } else {
                // 4. Fallback for Global/Super Admins
                $data = $globalTemplates->values();
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('available_tags', fn ($row) => \App\Services\TemplateVariableRegistry::displayForEvent($row->event_key))
                ->editColumn('is_active', function($row){
                    return $row->is_active 
                        ? '<span class="badge badge-success">'.__('sms_template.active').'</span>' 
                        : '<span class="badge badge-danger">'.__('sms_template.inactive').'</span>';
                })
                ->addColumn('action', function($row){
                    $tags = e(\App\Services\TemplateVariableRegistry::displayForEvent($row->event_key));
                    return '<button class="btn btn-primary btn-xs edit-template shadow" 
                        data-id="'.$row->id.'" 
                        data-key="'.$row->event_key.'"
                        data-name="'.$row->name.'"
                        data-body="'.e($row->body).'"
                        data-tags="'.$tags.'">
                        <i class="fa fa-pencil"></i> '.__('sms_template.edit').'</button>';
                })
                ->rawColumns(['is_active', 'action'])
                ->make(true);
        }

        return view('settings.sms_templates.index', [
            'variableRegistry' => \App\Services\TemplateVariableRegistry::all(),
        ]);
    }

    public function override(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        $request->validate([
            'event_key' => 'required|string',
            'body' => 'required|string',
        ]);

        $unknown = \App\Services\TemplateVariableRegistry::validateBody($request->event_key, $request->body);
        if (!empty($unknown)) {
            return response()->json(['message' => __('sms_template.unknown_tags', ['tags' => implode(', ', $unknown)])], 422);
        }

        // Save as an institution-specific template
        $template = SmsTemplate::updateOrCreate(
            ['institution_id' => $institutionId, 'event_key' => $request->event_key],
            [
                'name' => $request->name, // Inherit or update name
                'body' => $request->body,
                'available_tags' => $request->available_tags ?: \App\Services\TemplateVariableRegistry::displayForEvent($request->event_key),
                'is_active' => $request->has('is_active') ? 1 : 0
            ]
        );
        // echo "Saved Template: " . $template->id; // Debugging line 
        // dd($request->all(), $institutionId);
        return response()->json(['message' => __('sms_template.success_override')]);
    }
}