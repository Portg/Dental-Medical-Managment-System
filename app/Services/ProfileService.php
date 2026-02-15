<?php

namespace App\Services;

use App\Http\Helper\NameHelper;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    /**
     * Get current authenticated user.
     */
    public function getCurrentUser(): User
    {
        return User::where('id', Auth::User()->id)->first();
    }

    /**
     * Update user bio data.
     *
     * @param array $nameParts ['surname' => ..., 'othername' => ...]
     */
    public function updateBio(array $nameParts, string $email, ?string $phoneNumber, ?string $alternativeNo, ?string $nationalId): bool
    {
        return (bool) User::where('id', Auth::User()->id)->update([
            'surname' => $nameParts['surname'],
            'othername' => $nameParts['othername'],
            'email' => $email,
            'phone_no' => $phoneNumber,
            'alternative_no' => $alternativeNo,
            'nin' => $nationalId,
        ]);
    }

    /**
     * Update user avatar.
     *
     * @return string The saved filename.
     */
    public function updateAvatar($file): string
    {
        $hashed = time();
        $filename = $hashed . '_' . $file->getClientOriginalName();
        $file->move('uploads/users', $filename);

        User::where('id', Auth::User()->id)->update(['photo' => $filename]);

        return $filename;
    }

    /**
     * Change user password.
     */
    public function changePassword(string $newPassword): bool
    {
        return (bool) User::where('id', Auth::User()->id)->update([
            'password' => Hash::make($newPassword),
        ]);
    }
}
