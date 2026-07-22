<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SupportTicketController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->setPageTitle(__('support.page_title'));
    }

    /** Digitex support team = platform Super Admins. */
    private function isSupportAgent(?User $user = null): bool
    {
        $user = $user ?: Auth::user();
        return $user && $user->hasRole('Super Admin');
    }

    private function authorizeTicket(SupportTicket $ticket): void
    {
        if ($this->isSupportAgent()) {
            return;
        }
        if ((int) $ticket->user_id !== (int) Auth::id()) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $isSupport = $this->isSupportAgent($user);

        $query = SupportTicket::with(['user', 'institution', 'lastReplyBy'])
            ->withCount('messages');

        if (!$isSupport) {
            $query->where('user_id', $user->id);
        }

        // Filters
        $status = $request->get('status');
        $priority = $request->get('priority');
        $search = trim((string) $request->get('q', ''));

        if ($status && in_array($status, SupportTicket::STATUSES, true)) {
            $query->where('status', $status);
        }
        if ($priority && in_array($priority, SupportTicket::PRIORITIES, true)) {
            $query->where('priority', $priority);
        }
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('ticket_number', 'like', "%{$search}%");
            });
        }

        $tickets = $query
            ->orderByRaw("CASE WHEN status IN ('resolved','closed') THEN 1 ELSE 0 END")
            ->orderByDesc('last_reply_at')
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        // Stat counters (scoped)
        $countQuery = SupportTicket::query();
        if (!$isSupport) {
            $countQuery->where('user_id', $user->id);
        }
        $stats = [
            'total' => (clone $countQuery)->count(),
            'open' => (clone $countQuery)->whereIn('status', ['open', 'pending', 'answered'])->count(),
            'resolved' => (clone $countQuery)->whereIn('status', ['resolved', 'closed'])->count(),
            'urgent' => (clone $countQuery)->where('priority', 'urgent')->whereNotIn('status', ['resolved', 'closed'])->count(),
        ];

        return view('support.index', compact('tickets', 'stats', 'isSupport', 'status', 'priority', 'search'));
    }

    public function create()
    {
        $this->setPageTitle(__('support.new_ticket'));
        return view('support.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:160',
            'category' => 'required|in:' . implode(',', SupportTicket::CATEGORIES),
            'priority' => 'required|in:' . implode(',', SupportTicket::PRIORITIES),
            'message' => 'required|string|max:5000',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt',
        ]);

        $user = Auth::user();

        $ticket = SupportTicket::create([
            'institution_id' => $user->institute_id ?: $this->getInstitutionId(),
            'user_id' => $user->id,
            'subject' => $data['subject'],
            'category' => $data['category'],
            'priority' => $data['priority'],
            'status' => 'open',
            'last_reply_at' => now(),
            'last_reply_by' => $user->id,
            'user_last_read_at' => now(),
        ]);

        $ticket->update(['ticket_number' => 'TKT-' . date('y') . '-' . str_pad($ticket->id, 5, '0', STR_PAD_LEFT)]);

        $message = new SupportTicketMessage([
            'user_id' => $user->id,
            'is_support' => $this->isSupportAgent($user),
            'body' => $data['message'],
        ]);
        $this->attachFile($request, $ticket, $message);
        $ticket->messages()->save($message);

        $this->notifyNewTicket($ticket);

        return $this->successResponse(
            __('support.created_success', ['ticket' => $ticket->ticket_number]),
            route('support.show', $ticket->id)
        );
    }

    public function show(SupportTicket $ticket)
    {
        $this->authorizeTicket($ticket);

        $user = Auth::user();
        $isSupport = $this->isSupportAgent($user);

        // Mark as read for the viewing side
        if ($isSupport) {
            $ticket->update(['support_last_read_at' => now()]);
        } elseif ((int) $ticket->user_id === (int) $user->id) {
            $ticket->update(['user_last_read_at' => now()]);
        }

        $ticket->load(['user', 'institution', 'assignee', 'messages.user']);

        $this->setPageTitle($ticket->ticket_number . ' — ' . $ticket->subject);

        return view('support.show', compact('ticket', 'isSupport'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $this->authorizeTicket($ticket);

        $data = $request->validate([
            'message' => 'required|string|max:5000',
            'attachment' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt',
        ]);

        if ($ticket->status === 'closed') {
            return $this->replyResponse($request, $ticket, null, __('support.ticket_closed_error'), false);
        }

        $user = Auth::user();
        $isSupport = $this->isSupportAgent($user);

        $message = new SupportTicketMessage([
            'user_id' => $user->id,
            'is_support' => $isSupport,
            'body' => $data['message'],
        ]);
        $this->attachFile($request, $ticket, $message);
        $ticket->messages()->save($message);

        // Update ticket workflow state
        $ticket->last_reply_at = now();
        $ticket->last_reply_by = $user->id;
        if ($isSupport) {
            $ticket->status = 'answered';
            $ticket->support_last_read_at = now();
            if (!$ticket->assigned_to) {
                $ticket->assigned_to = $user->id;
            }
        } else {
            $ticket->status = $ticket->status === 'resolved' ? 'open' : 'pending';
            $ticket->user_last_read_at = now();
        }
        $ticket->save();

        $this->notifyReply($ticket, $message);

        $message->load('user');

        return $this->replyResponse($request, $ticket, $message, __('support.reply_sent'), true);
    }

    /** AJAX polling — return messages newer than a given id. */
    public function fetchMessages(Request $request, SupportTicket $ticket)
    {
        $this->authorizeTicket($ticket);

        $afterId = (int) $request->get('after', 0);
        $user = Auth::user();
        $isSupport = $this->isSupportAgent($user);

        $messages = $ticket->messages()
            ->with('user')
            ->where('id', '>', $afterId)
            ->get();

        // Mark read since the viewer is actively watching
        if ($messages->count()) {
            if ($isSupport) {
                $ticket->update(['support_last_read_at' => now()]);
            } else {
                $ticket->update(['user_last_read_at' => now()]);
            }
        }

        $html = $messages->map(fn ($m) => view('support.partials.message', [
            'm' => $m,
            'isSupport' => $isSupport,
        ])->render())->implode('');

        return response()->json([
            'html' => $html,
            'last_id' => $messages->last()->id ?? $afterId,
            'status' => $ticket->status,
            'status_label' => __('support.status_' . $ticket->status),
        ]);
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $this->authorizeTicket($ticket);

        $user = Auth::user();
        $isSupport = $this->isSupportAgent($user);

        $data = $request->validate([
            'status' => 'nullable|in:' . implode(',', SupportTicket::STATUSES),
            'priority' => 'nullable|in:' . implode(',', SupportTicket::PRIORITIES),
        ]);

        // Requesters may only resolve/close or reopen their own ticket
        if (!$isSupport) {
            $allowed = ['resolved', 'closed', 'open'];
            if (empty($data['status']) || !in_array($data['status'], $allowed, true)) {
                abort(403);
            }
            unset($data['priority']);
        }

        $changes = [];
        if (!empty($data['status']) && $data['status'] !== $ticket->status) {
            $ticket->status = $data['status'];
            $ticket->closed_at = in_array($data['status'], ['resolved', 'closed'], true) ? now() : null;
            $changes[] = __('support.system_status_changed', ['status' => __('support.status_' . $data['status'])]);
        }
        if ($isSupport && !empty($data['priority']) && $data['priority'] !== $ticket->priority) {
            $ticket->priority = $data['priority'];
            $changes[] = __('support.system_priority_changed', ['priority' => __('support.priority_' . $data['priority'])]);
        }

        if ($changes) {
            $ticket->save();
            $ticket->messages()->create([
                'user_id' => $user->id,
                'is_support' => $isSupport,
                'is_system' => true,
                'body' => implode(' ', $changes),
            ]);
            $this->notifyReply($ticket, null, true);
        }

        return $this->successResponse(__('support.updated_success'), route('support.show', $ticket->id));
    }

    // ---- helpers ----

    private function attachFile(Request $request, SupportTicket $ticket, SupportTicketMessage $message): void
    {
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store("support-tickets/{$ticket->id}", 'public');
            $message->attachment_path = $path;
            $message->attachment_name = $file->getClientOriginalName();
        }
    }

    private function replyResponse(Request $request, SupportTicket $ticket, ?SupportTicketMessage $message, string $msg, bool $ok)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok' => $ok,
                'message' => $msg,
                'status' => $ticket->status,
                'status_label' => __('support.status_' . $ticket->status),
                'html' => $message ? view('support.partials.message', [
                    'm' => $message,
                    'isSupport' => $this->isSupportAgent(),
                ])->render() : null,
                'last_id' => $message->id ?? 0,
            ], $ok ? 200 : 422);
        }

        return redirect()->route('support.show', $ticket->id)->with($ok ? 'success' : 'error', $msg);
    }

    private function notifyNewTicket(SupportTicket $ticket): void
    {
        try {
            $agents = User::role('Super Admin')->get();
            foreach ($agents as $agent) {
                InAppNotification::create([
                    'user_id' => $agent->id,
                    'institution_id' => $ticket->institution_id,
                    'type' => 'support_ticket',
                    'title' => __('support.notif_new_title'),
                    'message' => __('support.notif_new_message', [
                        'ticket' => $ticket->ticket_number,
                        'subject' => Str::limit($ticket->subject, 60),
                    ]),
                    'link' => route('support.show', $ticket->id),
                    'icon' => 'fa-life-ring',
                    'meta' => ['ticket_id' => $ticket->id],
                ]);
            }
        } catch (\Throwable $e) {
            // notifications are best-effort
        }
    }

    private function notifyReply(SupportTicket $ticket, ?SupportTicketMessage $message, bool $system = false): void
    {
        try {
            $authorIsSupport = $message ? $message->is_support : $this->isSupportAgent();

            if ($authorIsSupport) {
                // Notify the requester
                $targets = collect([$ticket->user]);
            } else {
                // Notify assigned agent, else all support agents
                $targets = $ticket->assigned_to
                    ? User::where('id', $ticket->assigned_to)->get()
                    : User::role('Super Admin')->get();
            }

            foreach ($targets->filter() as $target) {
                if ((int) $target->id === (int) Auth::id()) {
                    continue;
                }
                InAppNotification::create([
                    'user_id' => $target->id,
                    'institution_id' => $ticket->institution_id,
                    'type' => 'support_ticket',
                    'title' => __('support.notif_reply_title'),
                    'message' => __('support.notif_reply_message', ['ticket' => $ticket->ticket_number]),
                    'link' => route('support.show', $ticket->id),
                    'icon' => 'fa-life-ring',
                    'meta' => ['ticket_id' => $ticket->id],
                ]);
            }
        } catch (\Throwable $e) {
            // best-effort
        }
    }
}
