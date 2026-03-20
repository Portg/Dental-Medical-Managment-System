<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\MenuService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Override: the form field name for login input.
     */
    public function username()
    {
        return 'login';
    }

    /**
     * Override: resolve login input to email or username field.
     * AG-030: 统一返回"用户名或密码错误"
     */
    protected function credentials(Request $request)
    {
        $login = $request->input('login');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return [
            $field     => $login,
            'password' => $request->input('password'),
        ];
    }

    /**
     * Override: validate login request (field name is 'login' not 'email').
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);
    }

    /**
     * Override: check user status after successful authentication, then redirect by role.
     * AG-027: 离职用户不允许登录
     */
    protected function authenticated(Request $request, $user)
    {
        if (!$user->isActive()) {
            $this->guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['login' => __('auth.account_disabled')]);
        }

        return redirect($this->roleRedirectPath($user));
    }

    /**
     * Determine the post-login redirect URL from the user's menu tree (DB-driven).
     */
    private function roleRedirectPath($user): string
    {
        return app(MenuService::class)->getFirstUrlForUser($user);
    }
}
