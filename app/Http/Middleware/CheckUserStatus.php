<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->status !== true) {
            if ($request->user()->currentAccessToken()) {
                $request->user()->currentAccessToken()->delete();
            }

            // 🔹 If request expects JSON (API request)
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is deactivated. Please contact support.',
                ], 403);
            }
        }

        return $next($request);
    }
}
