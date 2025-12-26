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
        
        // Fetch templates. 
        // Logic: Show Global templates if Super Admin (inst_id null). 
        // Show School templates if School Admin.
        
        $query = SmsTemplate::query();
        
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        } else {
            $query->whereNull('institution_id');
        }

        $templates = $query->get();

        return view('settings.sms_templates.index', compact('templates'));
    }

    public function update(Request $request, $id)
    {
        $template = SmsTemplate::findOrFail($id);
        
        // Security check
        $institutionId = $this->getInstitutionId();
        if ($institutionId && $template->institution_id != $institutionId) {
            abort(403);
        }

        $validated = $request->validate([
            'body' => 'required|string',
            'is_active' => 'boolean'
        ]);

        $template->update($validated);

        return response()->json(['message' => 'Template updated successfully.']);
    }

    /**
     * For School Admins to override a Global Template
     * This creates a copy of the global event for their specific institution ID
     */
    public function override(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        if (!$institutionId) abort(403, 'Only schools can override global templates.');

        $validated = $request->validate([
            'event_key' => 'required|string',
            'name' => 'required|string',
            'body' => 'required|string',
            'available_tags' => 'nullable|string',
        ]);

        SmsTemplate::updateOrCreate(
            ['event_key' => $validated['event_key'], 'institution_id' => $institutionId],
            [
                'name' => $validated['name'],
                'body' => $validated['body'],
                'available_tags' => $validated['available_tags'],
                'is_active' => true
            ]
        );

        return response()->json(['message' => 'Custom template saved.']);
    }
}