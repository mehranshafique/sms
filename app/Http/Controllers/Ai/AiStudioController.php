<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\BaseController;
use App\Services\Ai\AiManager;
use App\Services\Ai\AiPlaceholderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiStudioController extends BaseController
{
    protected array $tools = ['draft_notice', 'report_comment', 'translate', 'summarize', 'improve', 'support_reply'];

    public function __construct(
        protected AiManager $ai,
        protected AiPlaceholderService $placeholders,
    ) {
        parent::__construct();
        $this->middleware('auth');
    }

    public function index()
    {
        $this->setPageTitle(__('ai.studio_title'));
        $institutionId = $this->getInstitutionId();

        return view('ai.studio', [
            'configured' => $this->ai->isConfigured($institutionId),
            'remaining'  => $this->ai->remaining($institutionId),
            'unlimited'  => $this->ai->isUnlimited($institutionId),
        ]);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'tool'     => 'required|string',
            'text'     => 'required|string|max:6000',
            'tone'     => 'nullable|string|max:40',
            'language' => 'nullable|string|max:40',
        ]);

        if (!in_array($data['tool'], $this->tools, true)) {
            return response()->json(['ok' => false, 'message' => __('ai.error_generic')], 422);
        }

        $institutionId = $this->getInstitutionId();
        if (in_array($data['tool'], ['draft_notice', 'support_reply'], true)) {
            $data['text'] .= "\n\n" . $this->placeholders->promptBlock(Auth::user(), $institutionId);
        }

        $built = $this->ai->buildToolMessages($data['tool'], $data);
        if (!$built) {
            return response()->json(['ok' => false, 'message' => __('ai.error_generic')], 422);
        }

        [$messages, $opts] = $built;
        $opts['institution_id'] = $institutionId;

        $result = $this->ai->ask('studio:' . $data['tool'], $messages, $opts);

        if (!$result['ok']) {
            return response()->json([
                'ok'      => false,
                'error'   => $result['error'],
                'message' => $this->errorMessage($result['error']),
            ], 200);
        }

        $content = (string) ($result['content'] ?? '');
        if (in_array($data['tool'], ['draft_notice', 'support_reply'], true)) {
            $content = $this->placeholders->apply($content, Auth::user(), $opts['institution_id'] ?? null);
        }

        return response()->json([
            'ok'        => true,
            'content'   => $content,
            'html'      => nl2br(e($content)),
            'remaining' => $result['remaining'],
        ]);
    }

    protected function errorMessage(?string $error): string
    {
        return match ($error) {
            'quota_exceeded' => __('ai.error_quota'),
            'not_configured' => __('ai.error_not_configured'),
            'no_access'      => __('ai.no_access_message'),
            default          => __('ai.error_generic'),
        };
    }
}
