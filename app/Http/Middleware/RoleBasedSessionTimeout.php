<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleBasedSessionTimeout
{
    protected array $timeouts = [
        'Client'                  => 120,
        'Account Specialist'      => 60,
        'Lead Account Specialist' => 30,
        'Operations'              => 60,
        'Lead Operations'         => 30,
        'Marketing'               => 60,
        'Client Success'          => 60,
        'Lead Client Success'     => 30,
    ];

    protected int $defaultTimeout = 60;

    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $timeout = $this->getTimeoutForUser($user);
        $timeoutSeconds = $timeout * 60;

        $lastActivity = session('last_activity_at');

        if ($lastActivity && (time() - $lastActivity) > $timeoutSeconds) {
            Auth::guard('web')->logout();
            session()->invalidate();
            session()->regenerateToken();

            return response()->json([
                'message' => 'Session expired due to inactivity.',
                'code'    => 'SESSION_EXPIRED',
            ], 401);
        }

        session(['last_activity_at' => time()]);

        return $next($request);
    }

    private function getTimeoutForUser($user): int
    {
        $roleName = $user->getRoleNames()->first();

        return $this->timeouts[$roleName] ?? $this->defaultTimeout;
    }
}