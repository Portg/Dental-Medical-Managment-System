<?php

namespace App\Http\Controllers;

use App\Http\Helper\NameHelper;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $user = User::where('id', Auth::User()->id)->first();
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

        $user = User::where('id', Auth::User()->id)->update(
            [
                'surname' => $nameParts['surname'],
                'othername' => $nameParts['othername'],
                'email' => $request->email,
                'phone_no' => $request->phone_number,
                'alternative_no' => $request->alternative_no,
                'nin' => $request->national_id,
            ]);
        if ($user) {
            return response()->json(['message' => __('messages.profile_updated_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred_later'), 'status' => false]);
    }


    public function update_Avatar(Request $request)
    {
        Validator::make($request->all(), [
            'avatar' => ['required', 'mimes:jpeg,bmp,png,jpg'],
        ])->validate();

        $file = $request->file('avatar');
        //generate hashed string
        $hashed = time();

        $filename = $hashed . '_' . $file->getClientOriginalName();
        $file->move('uploads/users', $filename);
        //now update the photo
        $updated_user = User::where('id', Auth::User()->id)->update(['photo' => $filename]);

        $user = User::where('id', $updated_user)->first();
        return redirect('/profile');

    }

    public function changePassword(Request $request)
    {
        Validator::make($request->only('old_password', 'new_password', 'confirm_password'), [
            'old_password' => 'required|string|min:6',
            'new_password' => 'required|string|min:6|different:old_password',
            'confirm_password' => 'required_with:new_password|same:new_password|string|min:6',
        ], [
            'confirm_password.required_with' => __('validation.required_with', ['attribute' => __('users.confirm_password')])
        ])->validate();

        //now update password
        $user = User::where('id', Auth::User()->id)->update(['password' => Hash::make($request->new_password)]);
        if ($user) {
            return response()->json(['message' => __('messages.password_changed_successfully'), 'status' => true]);
        }
        return response()->json(['message' => __('messages.error_occurred'), 'status' => false]);
    }

}
