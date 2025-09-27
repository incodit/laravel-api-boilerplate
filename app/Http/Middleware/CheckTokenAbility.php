<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenAbility
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();
        
        if (!$user || !$user->currentAccessToken()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!$user->currentAccessToken()->can($ability)) {
            return response()->json(['message' => 'Invalid token for this action'], 403);
        }

        return $next($request);
    }
}
