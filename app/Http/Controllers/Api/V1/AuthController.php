<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @group Authentication
 */
class AuthController extends ApiController
{
    /**
     * @unauthenticated
     * Login supports both username and email (AG-030: unified error message).
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $login = $request->input('login');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // AG-030: 统一返回错误信息，不区分用户不存在和密码错误
        if (!Auth::attempt([$field => $login, 'password' => $request->input('password')])) {
            return $this->error(__('auth.failed'), 401);
        }

        $user = Auth::user();

        // AG-027: 离职用户不允许登录
        if (!$user->isActive()) {
            Auth::logout();
            return $this->error(__('auth.account_disabled'), 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return $this->success([
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => [
                'id'        => $user->id,
                'username'  => $user->username,
                'surname'   => $user->surname,
                'othername' => $user->othername,
                'full_name' => $user->full_name,
                'email'     => $user->email,
                'role'      => $user->UserRole ? $user->UserRole->name : null,
                'branch_id' => $user->branch_id,
                'is_doctor' => $user->is_doctor,
            ],
        ], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success([
            'id'        => $user->id,
            'surname'   => $user->surname,
            'othername' => $user->othername,
            'full_name' => $user->full_name,
            'email'     => $user->email,
            'phone_no'  => $user->phone_no,
            'photo'     => $user->photo,
            'role'      => $user->UserRole ? $user->UserRole->name : null,
            'branch_id' => $user->branch_id,
            'is_doctor' => $user->is_doctor,
            'permissions' => $user->permissions()->pluck('slug'),
        ]);
    }
}
