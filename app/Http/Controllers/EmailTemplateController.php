<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Services\TemplateVariableRegistry;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Spatie\Permission\Middleware\PermissionMiddleware;

class EmailTemplateController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(PermissionMiddleware::class . ':sms_template.view')->only(['index']);
        $this->middleware(PermissionMiddleware::class . ':sms_template.update')->only(['override']);
        $this->setPageTitle(__('email_template.page_title'));
    }

    public function index(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        if ($request->ajax()) {
            $global = EmailTemplate::whereNull('institution_id')->get()->keyBy('event_key');
            $local = $institutionId
                ? EmailTemplate::where('institution_id', $institutionId)->get()->keyBy('event_key')
                : collect();

            $data = collect(array_merge($global->all(), $local->all()))->values();

            return DataTables::of($data)
                ->addIndexColumn()
                ->editColumn('available_tags', fn ($row) => TemplateVariableRegistry::displayForEvent($row->event_key))
                ->editColumn('is_active', fn ($row) => $row->is_active
                    ? '<span class="badge badge-success light">' . __('email_template.active') . '</span>'
                    : '<span class="badge badge-danger light">' . __('email_template.inactive') . '</span>')
                ->addColumn('action', function ($row) {
                    $tags = e(TemplateVariableRegistry::displayForEvent($row->event_key));
                    return '<button class="btn btn-primary btn-xs edit-email-template shadow"
                        data-key="' . e($row->event_key) . '"
                        data-name="' . e($row->name) . '"
                        data-subject="' . e($row->subject) . '"
                        data-body="' . e($row->body) . '"
                        data-tags="' . $tags . '">
                        <i class="fa fa-pencil"></i> ' . __('email_template.edit') . '</button>';
                })
                ->rawColumns(['is_active', 'action'])
                ->make(true);
        }

        $registry = TemplateVariableRegistry::all();

        return view('configuration.email_templates', compact('registry'));
    }

    public function override(Request $request)
    {
        $institutionId = $this->getInstitutionId();

        $request->validate([
            'event_key' => 'required|string',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $unknown = TemplateVariableRegistry::validateBody($request->event_key, $request->body . ' ' . $request->subject);
        if (!empty($unknown)) {
            return response()->json(['message' => __('email_template.unknown_tags', ['tags' => implode(', ', $unknown)])], 422);
        }

        EmailTemplate::updateOrCreate(
            ['institution_id' => $institutionId, 'event_key' => $request->event_key],
            [
                'name' => $request->name,
                'subject' => $request->subject,
                'body' => $request->body,
                'available_tags' => $request->available_tags ?? TemplateVariableRegistry::displayForEvent($request->event_key),
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        return response()->json(['message' => __('email_template.saved')]);
    }
}
