<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\BaseController;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Services\Ai\AiAssistantPrompt;
use App\Services\Ai\AiManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AiAssistantController extends BaseController
{
    public function __construct(
        protected AiManager $ai,
        protected AiAssistantPrompt $promptBuilder,
    ) {
        parent::__construct();
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->setPageTitle(__('ai.assistant_title'));

        $conversations = AiConversation::where('user_id', Auth::id())
            ->latest('updated_at')
            ->limit(30)
            ->get();

        $active = null;
        if ($request->filled('c')) {
            $active = AiConversation::where('user_id', Auth::id())->find($request->get('c'));
        }
        // No ?c= param means a fresh chat — do not auto-open the latest conversation.

        $messages = $active
            ? $active->messages()->get()
            : collect();

        $institutionId = $this->getInstitutionId();

        return view('ai.assistant', [
            'conversations' => $conversations,
            'active'        => $active,
            'messages'      => $messages,
            'configured'    => $this->ai->isConfigured($institutionId),
            'remaining'     => $this->ai->remaining($institutionId),
            'unlimited'     => $this->ai->isUnlimited($institutionId),
        ]);
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'message'         => 'required|string|max:4000',
            'conversation_id' => 'nullable|integer',
        ]);

        $userId = Auth::id();

        $conversation = null;
        if (!empty($data['conversation_id'])) {
            $conversation = AiConversation::where('user_id', $userId)->find($data['conversation_id']);
        }
        if (!$conversation) {
            $conversation = AiConversation::create([
                'institution_id' => $this->getInstitutionId(),
                'user_id'        => $userId,
                'title'          => Str::limit($data['message'], 60, ''),
                'context'        => 'assistant',
            ]);
        }

        AiMessage::create([
            'ai_conversation_id' => $conversation->id,
            'role'               => 'user',
            'content'            => $data['message'],
        ]);

        // Last 10 messages (5 turns) — keeps context without amplifying old generic replies
        $history = AiMessage::where('ai_conversation_id', $conversation->id)
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->sortBy('id')
            ->values()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->all();

        $institutionId = $this->getInstitutionId();

        $payload = array_merge(
            [['role' => 'system', 'content' => $this->promptBuilder->build(Auth::user(), $institutionId)]],
            $history
        );

        $result = $this->ai->ask('assistant', $payload, [
            'institution_id' => $institutionId,
            'temperature'    => 0.4,
            'max_tokens'     => 1200,
        ]);

        // Retry once with a clean context if the model deflects instead of answering
        if ($result['ok'] && $this->isGenericDeflection($result['content'] ?? '')) {
            $retryPayload = [
                ['role' => 'system', 'content' => $this->promptBuilder->build(Auth::user(), $institutionId)],
                ['role' => 'user', 'content' => $data['message']],
            ];
            $result = $this->ai->ask('assistant', $retryPayload, [
                'institution_id' => $institutionId,
                'temperature'    => 0.3,
                'max_tokens'     => 1200,
            ]);
        }

        if (!$result['ok']) {
            return response()->json([
                'ok'              => false,
                'error'           => $result['error'],
                'message'         => $this->errorMessage($result['error']),
                'conversation_id' => $conversation->id,
            ], 200);
        }

        $reply = AiMessage::create([
            'ai_conversation_id' => $conversation->id,
            'role'               => 'assistant',
            'content'            => $result['content'],
        ]);

        $conversation->touch();

        return response()->json([
            'ok'              => true,
            'reply'           => $reply->content,
            'reply_html'      => nl2br(e($reply->content)),
            'conversation_id' => $conversation->id,
            'title'           => $conversation->title,
            'remaining'       => $result['remaining'],
        ]);
    }

    public function destroy(AiConversation $conversation)
    {
        abort_unless($conversation->user_id === Auth::id(), 403);
        $conversation->delete();

        return redirect()->route('ai.assistant')->with('success', __('ai.conversation_deleted'));
    }

    protected function errorMessage(?string $error): string
    {
        return match ($error) {
            'quota_exceeded'   => __('ai.error_quota'),
            'not_configured'   => __('ai.error_not_configured'),
            'no_access'        => __('ai.no_access_message'),
            'provider_error'   => __('ai.error_provider'),
            'empty_response'   => __('ai.error_provider'),
            default            => __('ai.error_generic'),
        };
    }

    protected function isGenericDeflection(string $content): bool
    {
        $lower = strtolower(trim($content));
        if ($lower === '' || strlen($content) > 160) {
            return false;
        }

        $patterns = [
            "i'm here to help",
            'how can i assist',
            'what do you need assistance',
            'please let me know what you need',
            'what do you need help with',
            'absolutely! what do you need',
            'of course! how can i assist',
            'how can i help you today',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
