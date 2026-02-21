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
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->error('Invalid credentials', 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return $this->success([
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => [
                'id'        => $user->id,
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
