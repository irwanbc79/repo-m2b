<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'Gagal masuk menggunakan Google. Harap coba lagi.']);
        }

        // Check if a user already exists with this Google ID
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            // Update token if changed
            $user->update([
                'google_token' => $googleUser->token,
                'google_refresh_token' => $googleUser->refreshToken ?? $user->google_refresh_token,
            ]);

            Auth::login($user);
            return redirect()->intended('/admin'); // Redirect to filament dashboard
        }

        // Check if user exists with the same email address
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // Link Google Account to existing user
            $user->update([
                'google_id' => $googleUser->getId(),
                'google_token' => $googleUser->token,
                'google_refresh_token' => $googleUser->refreshToken ?? null,
            ]);

            Auth::login($user);
            return redirect()->intended('/admin');
        }

        // Create a new user (customer by default)
        // Extract names
        $fullName = $googleUser->getName();
        $nameParts = explode(' ', $fullName, 2);
        $firstName = $nameParts[0] ?? 'Google';
        $lastName = $nameParts[1] ?? 'User';

        $newUser = User::create([
            'name' => $fullName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'google_token' => $googleUser->token,
            'google_refresh_token' => $googleUser->refreshToken ?? null,
            'role' => 'customer',
            'is_active' => true,
            'password' => Hash::make(Str::random(16)), // Dummy password
        ]);

        Auth::login($newUser);
        return redirect()->intended('/admin');
    }
}
