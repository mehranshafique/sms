<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InstitutionSetting;
use App\Models\ChatbotKeyword;
use App\Enums\ChatbotPortalRole;
use App\Models\ChatSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Carbon\Carbon;

class ChatbotSettingController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        
        // Role-Based Security: Enforce explicit permissions
        $this->middleware(PermissionMiddleware::class . ':setting.view')->only(['index', 'getSessions']);
        $this->middleware(PermissionMiddleware::class . ':setting.manage')->only(['storeConfig', 'storeKeyword', 'updateKeyword', 'destroyKeyword', 'destroySession', 'bulkDestroySession']);
        
        $this->setPageTitle(__('chatbot.page_title'));
    }

    public function index()
    {
        $institutionId = $this->getInstitutionId();
        
        // 1. Get Keywords
        $keywords = ChatbotKeyword::where('institution_id', $institutionId)
            ->latest()
            ->get();

        // 2. Get Settings
        $keys = [
            'chatbot_session_timeout', 
            'chatbot_enable_whatsapp', 
            'chatbot_enable_sms', 
            'chatbot_enable_telegram'
        ];
        
        $settings = InstitutionSetting::where('institution_id', $institutionId)
            ->whereIn('key', $keys)
            ->pluck('value', 'key');
            
        $config = [
            'timeout'  => $settings['chatbot_session_timeout'] ?? 15,
            'whatsapp' => $settings['chatbot_enable_whatsapp'] ?? 0,
            'sms'      => $settings['chatbot_enable_sms'] ?? 0,
            'telegram' => $settings['chatbot_enable_telegram'] ?? 0,
        ];

        $baseUrl = rtrim(config('app.url'), '/');
        $secret = config('services.chatbot.webhook_secret');
        $secretQuery = $secret ? '?secret=' . urlencode($secret) : '';
        $webhookUrls = [
            'infobip' => $baseUrl . '/api/v1/chatbot/webhook/infobip' . $secretQuery,
            'twilio' => $baseUrl . '/api/v1/chatbot/webhook/twilio' . $secretQuery,
            'meta' => $baseUrl . '/api/v1/chatbot/webhook/meta' . $secretQuery,
            'telegram' => $baseUrl . '/api/v1/chatbot/webhook/telegram' . $secretQuery,
            'legacy' => $baseUrl . '/api/v1/chatbot/webhook' . $secretQuery,
        ];

        $portalRoles = ChatbotPortalRole::options();

        return view('chatbot.settings', compact('keywords', 'config', 'webhookUrls', 'portalRoles'));
    }

    public function storeConfig(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        $request->validate([
            'timeout' => 'required|integer|min:1|max:1440',
        ]);

        InstitutionSetting::set($institutionId, 'chatbot_session_timeout', $request->timeout);
        InstitutionSetting::set($institutionId, 'chatbot_enable_whatsapp', $request->has('whatsapp') ? 1 : 0);
        InstitutionSetting::set($institutionId, 'chatbot_enable_sms', $request->has('sms') ? 1 : 0);
        InstitutionSetting::set($institutionId, 'chatbot_enable_telegram', $request->has('telegram') ? 1 : 0);

        return back()->with('success', __('chatbot.config_saved'));
    }

    // --- Active Sessions Methods ---
    public function getSessions(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        $query = ChatSession::query();
        
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }
        
        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('checkbox', function($row){
                if(auth()->user()->can('setting.manage')){
                    return '<div class="form-check custom-checkbox checkbox-primary check-lg me-3">
                                <input type="checkbox" class="form-check-input single-checkbox" value="'.$row->id.'">
                                <label class="form-check-label"></label>
                            </div>';
                }
                return '';
            })
            // NEW: Compile complete User Details directly for the frontend modal to render
            ->addColumn('user_details', function($row) {
                $details = "<ul class='list-unstyled mb-0 text-start'>";
                $details .= "<li class='mb-2'><strong>Phone:</strong> " . ($row->phone_number ?? 'N/A') . "</li>";
                $details .= "<li class='mb-2'><strong>User Type:</strong> " . ucfirst($row->user_type ?? 'Unknown') . "</li>";
                
                $foundUser = false;

                // 1. Try resolving by explicit User ID
                if ($row->user_id) {
                    if ($row->user_type === 'student') {
                        $student = \App\Models\Student::find($row->user_id);
                        if ($student) {
                            $foundUser = true;
                            $details .= "<li class='mb-2'><strong>Name:</strong> {$student->full_name}</li>";
                            $details .= "<li class='mb-2'><strong>Admission No:</strong> {$student->admission_number}</li>";
                            $enrollment = $student->enrollments()->latest()->first();
                            $class = $enrollment && $enrollment->classSection ? $enrollment->classSection->name : 'N/A';
                            $details .= "<li class='mb-2'><strong>Class:</strong> {$class}</li>";
                        }
                    } elseif ($row->user_type === 'parent') {
                        $parent = \App\Models\StudentParent::find($row->user_id);
                        if ($parent) {
                            $foundUser = true;
                            $father = $parent->father_name ? "{$parent->father_name} ({$parent->father_phone})" : 'N/A';
                            $mother = $parent->mother_name ? "{$parent->mother_name} ({$parent->mother_phone})" : 'N/A';
                            $details .= "<li class='mb-2'><strong>Father:</strong> {$father}</li>";
                            $details .= "<li class='mb-2'><strong>Mother:</strong> {$mother}</li>";
                        }
                    } else {
                        // Fallback for Staff/Admins
                        $user = \App\Models\User::find($row->user_id);
                        if ($user) {
                            $foundUser = true;
                            $details .= "<li class='mb-2'><strong>Name:</strong> {$user->name}</li>";
                            $details .= "<li class='mb-2'><strong>Email:</strong> {$user->email}</li>";
                            $details .= "<li class='mb-2'><strong>Shortcode:</strong> {$user->shortcode}</li>";
                        }
                    }
                }

                // 2. Fallback: Try resolving by Phone Number if not found
                if (!$foundUser && $row->phone_number) {
                    $cleanPhone = preg_replace('/[^0-9]/', '', $row->phone_number);
                    
                    if (strlen($cleanPhone) >= 8) {
                        // Try Parent
                        $parent = \App\Models\StudentParent::where('father_phone', 'like', "%$cleanPhone%")
                            ->orWhere('mother_phone', 'like', "%$cleanPhone%")
                            ->orWhere('guardian_phone', 'like', "%$cleanPhone%")
                            ->first();
                        
                        if ($parent) {
                            $foundUser = true;
                            $details .= "<li class='mb-2 text-info'><i class='fa fa-search me-1'></i> Found via Phone (Parent)</li>";
                            $details .= "<li class='mb-2'><strong>Father:</strong> " . ($parent->father_name ?: 'N/A') . "</li>";
                            $details .= "<li class='mb-2'><strong>Mother:</strong> " . ($parent->mother_name ?: 'N/A') . "</li>";
                        } else {
                            // Try User (Staff/Admin)
                            $user = \App\Models\User::where('phone', 'like', "%$cleanPhone%")->first();
                            if ($user) {
                                $foundUser = true;
                                $details .= "<li class='mb-2 text-info'><i class='fa fa-search me-1'></i> Found via Phone (Staff)</li>";
                                $details .= "<li class='mb-2'><strong>Name:</strong> {$user->name}</li>";
                                $details .= "<li class='mb-2'><strong>Email:</strong> {$user->email}</li>";
                            } else {
                                // Try Student
                                $student = \App\Models\Student::where('mobile_number', 'like', "%$cleanPhone%")->first();
                                if ($student) {
                                    $foundUser = true;
                                    $details .= "<li class='mb-2 text-info'><i class='fa fa-search me-1'></i> Found via Phone (Student)</li>";
                                    $details .= "<li class='mb-2'><strong>Name:</strong> {$student->full_name}</li>";
                                    $details .= "<li class='mb-2'><strong>Admission No:</strong> {$student->admission_number}</li>";
                                }
                            }
                        }
                    }
                }

                if (!$foundUser) {
                    $details .= "<li class='mb-2 text-warning'><i class='fa fa-exclamation-triangle me-1'></i> Profile not yet linked (Awaiting Authentication)</li>";
                }
                
                $details .= "</ul>";
                return $details;
            })
            ->editColumn('institution_id', function($row) {
                if (!$row->institution_id) {
                    return 'Global';
                }
                $institution = \App\Models\Institution::find($row->institution_id);
                return $institution ? "{$institution->name} ({$institution->code})" : "Unknown ID: {$row->institution_id}";
            })
            ->editColumn('phone_number', function($row) {
                return $row->phone_number ?? 'N/A';
            })
            ->editColumn('user_type', function($row) {
                return ucfirst($row->user_type ?? 'Unknown');
            })
            ->editColumn('attempts', function($row) {
                return '<span class="badge badge-circle badge-light text-primary border border-primary">'.($row->attempts ?? 0).'</span>';
            })
            ->editColumn('status', function($row) {
                $status = $row->status ?? 'UNKNOWN';
                $badges = [
                    'ACTIVE' => 'badge-success',
                    'AWAITING_ID' => 'badge-warning',
                    'AWAITING_OTP' => 'badge-info',
                    'CHILD_SELECT' => 'badge-primary'
                ];
                $class = $badges[$status] ?? 'badge-secondary';
                $statusText = str_replace('_', ' ', $status);
                return '<span class="badge light ' . $class . '">' . $statusText . '</span>';
            })
            ->editColumn('created_at', function($row) {
                return $row->created_at ? Carbon::parse($row->created_at)->format('d M Y, h:i A') : '-';
            })
            ->editColumn('updated_at', function($row) {
                return $row->updated_at ? Carbon::parse($row->updated_at)->format('d M Y, h:i A') : '-';
            })
            ->editColumn('expires_at', function($row) {
                return $row->expires_at ? Carbon::parse($row->expires_at)->format('d M Y, h:i A') : '-';
            })
            ->editColumn('otp', function($row) {
                return $row->otp ?: '<span class="text-muted">-</span>';
            })
            ->editColumn('locale', function($row) {
                return strtoupper($row->locale ?? 'N/A');
            })
            ->addColumn('action', function($row) {
                if(auth()->user()->can('setting.manage')) {
                    return '<button class="btn btn-danger btn-xs shadow end-session-btn" data-id="'.$row->id.'" title="'.__('chatbot.end_session').'"><i class="fa fa-times me-1"></i> '.__('chatbot.end_session').'</button>';
                }
                return '';
            })
            ->rawColumns(['checkbox', 'status', 'otp', 'attempts', 'action', 'user_details'])
            ->make(true);
    }

    public function destroySession($id)
    {
        $institutionId = $this->getInstitutionId();
        
        $query = ChatSession::query();
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }
        
        $session = $query->findOrFail($id);
        $session->delete();
        
        return response()->json(['message' => __('chatbot.session_ended_success')]);
    }

    public function bulkDestroySession(Request $request)
    {
        $ids = $request->ids;
        if (!empty($ids)) {
            $institutionId = $this->getInstitutionId();
            
            $query = ChatSession::whereIn('id', $ids);
            
            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            }
            
            $query->delete();
            return response()->json(['success' => __('chatbot.session_ended_success') ?? 'Sessions successfully ended.']);
        }
        return response()->json(['error' => __('chatbot.something_went_wrong') ?? 'Something went wrong!']);
    }

    // --- Keyword Methods ---
    public function storeKeyword(Request $request)
    {
        $institutionId = $this->getInstitutionId();
        
        $request->validate([
            'keyword' => [
                'required', 'string', 'max:50',
                Rule::unique('chatbot_keywords')->where(function ($query) use ($institutionId) {
                    return $query->where('institution_id', $institutionId);
                })
            ],
            'language' => 'required|in:en,fr',
            'portal_role' => ['required', Rule::in(array_column(ChatbotPortalRole::cases(), 'value'))],
            'welcome_message' => 'required|string|max:1000',
        ]);

        ChatbotKeyword::create([
            'institution_id' => $institutionId,
            'keyword' => strtolower(trim($request->keyword)),
            'language' => $request->language,
            'portal_role' => $request->portal_role,
            'welcome_message' => $request->welcome_message
        ]);

        return back()->with('success', __('chatbot.keyword_created'));
    }

    public function updateKeyword(Request $request, $id)
    {
        $institutionId = $this->getInstitutionId();
        $keyword = ChatbotKeyword::where('institution_id', $institutionId)->findOrFail($id);

        $request->validate([
            'keyword' => [
                'required', 'string', 'max:50',
                Rule::unique('chatbot_keywords')->where(function ($query) use ($institutionId) {
                    return $query->where('institution_id', $institutionId);
                })->ignore($id)
            ],
            'language' => 'required|in:en,fr',
            'portal_role' => ['required', Rule::in(array_column(ChatbotPortalRole::cases(), 'value'))],
            'welcome_message' => 'required|string|max:1000',
        ]);

        $keyword->update([
            'keyword' => strtolower(trim($request->keyword)),
            'language' => $request->language,
            'portal_role' => $request->portal_role,
            'welcome_message' => $request->welcome_message
        ]);

        return back()->with('success', __('chatbot.keyword_updated'));
    }

    public function destroyKeyword($id)
    {
        $institutionId = $this->getInstitutionId();
        $keyword = ChatbotKeyword::where('institution_id', $institutionId)->findOrFail($id);
        
        $keyword->delete();
        
        return back()->with('success', __('chatbot.keyword_deleted'));
    }
}