<?php

namespace App\Http\Controllers;

use App\Http\Helper\NameHelper;
use App\Rules\StrongPassword;
use App\Services\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    private ProfileService $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = $this->profileService->getCurrentUser();
        return view('profile.index', compact('user'));
    }

    //update user bio data info
    public function update_Bio(Request $request)
    {
        if ($request->filled('full_name')) {
            Validator::make($request->all(), [
                'full_name' => 'required|string|min:2',
                'email' => 'required|email',
            ])->validate();
            $nameParts = NameHelper::split($request->full_name);
        } else {
            Validator::make($request->all(), [
                'surname' => 'required|string',
                'othername' => 'required|string',
                'email' => 'required|email',
            ])->validate();
            $nameParts = ['surname' => $request->surname, 'othername' => $request->othername];
        }

        $status = $this->profileService->updateBio(
            $nameParts,
            $request->email,
            $request->phone_number,
            $request->alternative_no,
            $request->national_id
        );

        if ($status) {
            return response()->json(['message' => __('messages.profile_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }


    public function update_Avatar(Request $request)
    {
        Validator::make($request->all(), [
            'avatar' => ['required', 'mimes:jpeg,bmp,png,jpg'],
        ])->validate();

        $this->profileService->updateAvatar($request->file('avatar'));

        return redirect('/profile');
    }

    public function changePassword(Request $request)
    {
        Validator::make($request->only('old_password', 'new_password', 'confirm_password'), [
            'old_password' => 'required|string',
            'new_password' => ['required', 'string', 'different:old_password', new StrongPassword],
            'confirm_password' => 'required_with:new_password|same:new_password|string',
        ], [
            'confirm_password.required_with' => __('validation.required_with', ['attribute' => __('users.confirm_password')])
        ])->validate();

        $status = $this->profileService->changePassword($request->new_password);
        if ($status) {
            return response()->json(['message' => __('messages.password_changed_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }
}
