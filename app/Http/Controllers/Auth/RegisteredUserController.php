<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Mail\AccountActivationMail;
use App\Models\AccountActivationToken;
use App\Models\Property;
use App\Models\Syndic;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        return DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'syndic',
                'email_verified_at' => null,
            ]);

            $property = Property::create([
                'name' => $request->property_name,
                'address' => $request->property_address,
            ]);

            Syndic::create([
                'user_id' => $user->id,
                'property_id' => $property->id,
            ]);

            $token = Str::random(64);

            AccountActivationToken::create([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addHours(48),
            ]);

            Mail::to($user->email)->queue(new AccountActivationMail($token));

            return redirect()->route('login')->with('status', 'Votre compte syndic a été créé avec succès. Vérifiez votre email pour l’activation.');
        });
    }
}
