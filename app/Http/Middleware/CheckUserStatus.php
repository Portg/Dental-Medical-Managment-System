<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * AG-027: 离职状态的用户不允许通过任何渠道（Web/API）登录
 */
class CheckUserStatus
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && !$user->isActive()) {
            if ($request->expectsJson()) {
                // API: revoke current token and return 403
                $user->currentAccessToken()?->delete();
                return response()->json([
                    'success' => false,
                    'message' => __('auth.account_disabled'),
                ], 403);
            }

            // Web: logout and redirect to login
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['login' => __('auth.account_disabled')]);
        }

        return $next($request);
    }
}
