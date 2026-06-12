<?php

namespace App\Http\Middleware;

use App\Services\Ai\AiManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the optional AI module. Applied ONLY to /ai routes, so schools
 * without an AI plan are completely unaffected — the rest of the system never
 * touches this middleware.
 */
class EnsureAiAccess
{
    public function __construct(protected AiManager $ai) {}

    public function handle(Request $request, Closure $next): Response
    {
        $institutionId = $this->ai->resolveInstitutionId();

        if (!$this->ai->hasPlanAccess($institutionId)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'ok'      => false,
                    'error'   => 'no_access',
                    'message' => __('ai.no_access_message'),
                ], 403);
            }

            return response()->view('ai.upsell', [], 403);
        }

        return $next($request);
    }
}
