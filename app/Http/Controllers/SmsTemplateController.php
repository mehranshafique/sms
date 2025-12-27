<?php

namespace App\Http\Controllers;

use App\Models\SmsTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SmsTemplateController extends BaseController
{
    public function __construct()
    {
        $this->setPageTitle('SMS Templates');
    }

    public function index()
    {
        $institutionId = $this->getInstitutionId();
        
        if (!$institutionId) {
            // Super Admin: Show only Global Templates
            $templates = SmsTemplate::whereNull('institution_id')->get();
        } else {
            // School Admin: Fetch Global AND School Specific
            // We key them by 'event_key' to merge them easily
            
            $globalTemplates = SmsTemplate::whereNull('institution_id')->get()->keyBy('event_key');
            $schoolTemplates = SmsTemplate::where('institution_id', $institutionId)->get()->keyBy('event_key');

            // Merge: School templates take precedence over Global ones
            $templates = $globalTemplates->merge($schoolTemplates);
        }

        return view('settings.sms_templates.index', compact('templates'));
    }

    public function update(Request $request, $id)
    {
        $template = SmsTemplate::findOrFail($id);
        $institutionId = $this->getInstitutionId();

        $validated = $request->validate([
            'body' => 'required|string',
            'is_active' => 'boolean' // Handles the activation toggle
        ]);

        // Scenario 1: Super Admin updating Global Template
        if (!$institutionId) {
            $template->update($validated);
            return response()->json(['message' => 'Global template updated.']);
        }

        // Scenario 2: School Admin updating their OWN Custom Template
        if ($template->institution_id == $institutionId) {
            $template->update($validated);
            return response()->json(['message' => 'Template updated.']);
        }

        // Scenario 3: School Admin "Activating" or "Editing" a Global Template (Implicit Override)
        // We create a NEW record for this school based on the global one
        if ($template->institution_id === null) {
            SmsTemplate::create([
                'institution_id' => $institutionId,
                'event_key' => $template->event_key,
                'name' => $template->name,
                'available_tags' => $template->available_tags,
                'body' => $validated['body'], // Use the new body from request
                'is_active' => $request->boolean('is_active') // Use new status
            ]);
            
            return response()->json(['message' => 'Template activated and customized for your school.']);
        }

        abort(403, 'Unauthorized action.');
    }
}