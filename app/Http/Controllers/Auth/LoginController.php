<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function pending(string $email)
    {
        return view('auth.activation-pending', ['email' => $email]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if ($user && is_null($user->email_verified_at)) {
            return redirect()->route('activation.pending', ['email' => $credentials['email']]);
        }

        $loggedIn = Auth::attempt($credentials, $request->boolean('remember'));

        if (! $loggedIn && $user && password_verify($credentials['password'], $user->password)) {
            Auth::login($user, $request->boolean('remember'));
            $loggedIn = true;
        }

        if ($loggedIn) {
            $request->session()->regenerate();

            /** @var \App\Models\User $user */
            $user = Auth::user();
            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->withProperties([
                    'action_type' => 'login',
                    'property_id' => $user->syndic?->property_id ?? $user->owner?->property_id,
                ])
                ->log('User logged in');

            return redirect()->intended(route('dashboard'));
        }

        return redirect()->route('login')->withErrors([
            'email' => 'Les identifiants fournis sont incorrects.',
        ])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
