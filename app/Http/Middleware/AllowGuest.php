<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;

class AllowGuest
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        
        // If no token provided, allow as guest
        if (!$token) {
            $request->attributes->set('is_guest', true);
            return $next($request);
        }

        // If token is provided, validate it
        $accessToken = PersonalAccessToken::findToken($token);
        
        // If token is invalid, return error
        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token'
            ], 401);
        }

        // Token is valid, authenticate the user
        $user = $accessToken->tokenable;
        $request->setUserResolver(fn () => $user);
        
        return $next($request);
    }
}