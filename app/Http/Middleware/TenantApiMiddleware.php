<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantApiMiddleware
{
    /**
     * Handle an incoming request.
     * Ensures every API request is scoped to the authenticated token's institution.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->institute_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated or invalid tenant context.'
            ], 401);
        }

        // Globally scope queries for this request
        // This ensures the Chatbot can NEVER accidentally see data from another school
        \Illuminate\Support\Facades\URL::defaults(['institution_id' => $user->institute_id]);

        return $next($request);
    }
}