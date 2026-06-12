<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\BaseController;
use App\Services\Ai\AiEmbedService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AiEmbedController extends BaseController
{
    public function __construct(protected AiEmbedService $embed)
    {
        parent::__construct();
        $this->middleware('auth');
    }

    public function tools()
    {
        return response()->json(['tools' => $this->embed->toolRegistry()]);
    }

    public function run(Request $request)
    {
        $data = $request->validate([
            'tool'   => 'required|string|max:80',
            'params' => 'nullable|array',
        ]);

        try {
            $result = $this->embed->run($data['tool'], $data['params'] ?? []);
        } catch (ValidationException $e) {
            return response()->json([
                'ok'      => false,
                'message' => collect($e->errors())->flatten()->first() ?? __('ai.error_generic'),
                'errors'  => $e->errors(),
            ], 422);
        }

        return response()->json([
            'ok'        => true,
            'content'   => $result['text'] ?? '',
            'html'      => nl2br(e($result['text'] ?? '')),
            'type'      => $result['type'] ?? null,
            'meta'      => collect($result)->except('text')->all(),
        ]);
    }
}
