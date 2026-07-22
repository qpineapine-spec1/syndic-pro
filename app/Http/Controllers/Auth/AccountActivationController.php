<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivateAccountRequest;
use App\Http\Requests\StoreOwnerRequest;
use App\Mail\AccountActivationMail;
use App\Models\AccountActivationToken;
use App\Models\Owner;
use App\Models\OwnerInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AccountActivationController extends Controller
{
    public function show($token)
    {
        $invitation = OwnerInvitation::where('token_hash', hash('sha256', $token))->first();
        if ($invitation) {
            if ($invitation->used_at || now()->gt($invitation->expires_at)) {
                return view('auth.activation-invalid');
            }

            return view('auth.activate_owner', ['token' => $token, 'email' => $invitation->email]);
        }

        $tokenRecord = AccountActivationToken::where('token_hash', hash('sha256', $token))->first();

        if (!$tokenRecord || $tokenRecord->used_at || now()->gt($tokenRecord->expires_at)) {
            return view('auth.activation-invalid');
        }

        return view('auth.activate', ['token' => $token]);
    }

    public function showResendForm()
    {
        return view('auth.resend-activation');
    }

    public function store(Request $request, $token)
    {
        // First try owner invitations
        $invitation = OwnerInvitation::where('token_hash', hash('sha256', $token))->first();
        if ($invitation) {
            if ($invitation->used_at || now()->gt($invitation->expires_at)) {
                return view('auth.activation-invalid');
            }

            // Inject known property_id/email from invitation so StoreOwnerRequest validation passes
            $request->merge(['property_id' => $invitation->property_id]);
            if ($invitation->email && !$request->filled('email')) {
                $request->merge(['email' => $invitation->email]);
            }

            // Normalize status to 'proprietaire' or 'locataire'
            if ($request->filled('status')) {
                $status = strtolower((string) $request->input('status', ''));
                $normalized = match ($status) {
                    'locataire', 'tenant', 'locataire/proprietaire' => 'locataire',
                    'proprietaire', 'owner', 'propriétaire' => 'proprietaire',
                    default => 'proprietaire',
                };
                $request->merge(['status' => $normalized]);
                
                // Set is_tenant based on the occupation status
                $request->merge(['is_tenant' => $normalized === 'locataire']);
            }

            if (!$request->filled('is_tenant')) {
                $request->merge(['is_tenant' => false]);
            }

            // Combine password rules and owner rules
            $activateRules = (new ActivateAccountRequest())->rules();
            $ownerRules = (new StoreOwnerRequest())->rules();
            $rules = array_merge($activateRules, $ownerRules);

            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);
            $validator->sometimes('mezzanine_surface', ['required', 'numeric'], function ($input) {
                return isset($input->has_mezzanine) && $input->has_mezzanine == true;
            });
            $validator->sometimes(['contract_start_date', 'contract_end_date', 'real_owner_name'], ['required'], function ($input) {
                return isset($input->status) && $input->status === 'locataire';
            });
            $validator->validate();

            $data = $validator->validated();

            return DB::transaction(function () use ($data, $invitation) {
                $email = $invitation->email ?? $data['email'];

                $user = User::create([
                    'name' => $data['name'],
                    'email' => $email,
                    'password' => Hash::make($data['password']),
                    'role' => 'copropriétaire',
                    'email_verified_at' => now(),
                ]);

                $owner = Owner::create([
                    'property_id' => $invitation->property_id,
                    'user_id' => $user->id,
                    'status' => $data['status'],
                    'is_tenant' => (bool) $data['is_tenant'],
                    'lot_surface' => $data['lot_surface'],
                    'surface_confirmation' => $data['surface_confirmation'],
                    'has_mezzanine' => (bool) $data['has_mezzanine'],
                    'mezzanine_surface' => $data['mezzanine_surface'] ?? null,
                    'office_number' => $data['office_number'] ?? null,
                    'floor' => $data['floor'] ?? null,
                    'telephone' => $data['telephone'] ?? $invitation->phone ?? null,
                    'real_owner_name' => $data['status'] === 'locataire' ? ($data['real_owner_name'] ?? null) : null,
                    'is_council_member' => (bool) ($data['is_council_member'] ?? false),
                ]);

                // Si le copropriétaire s'inscrit en tant que locataire, on enregistre
                // directement son contrat locatif afin qu'il apparaisse chez le syndic
                if ($data['status'] === 'locataire' && !empty($data['contract_start_date']) && !empty($data['contract_end_date'])) {
                    \App\Models\Tenant::create([
                        'owner_id' => $owner->id,
                        'tenant_name' => $data['name'],
                        'tenant_phone' => $data['telephone'] ?? $invitation->phone ?? null,
                        'contract_start_date' => $data['contract_start_date'],
                        'contract_end_date' => $data['contract_end_date'],
                        'is_active' => true,
                    ]);
                }

                $invitation->used_at = now();
                $invitation->save();

                return redirect()->route('login')->with('status', 'Votre compte est activé.');
            });
        }

        // Fallback to legacy token for existing user
        $tokenRecord = AccountActivationToken::where('token_hash', hash('sha256', $token))->first();

        if (!$tokenRecord || $tokenRecord->used_at || now()->gt($tokenRecord->expires_at)) {
            return view('auth.activation-invalid');
        }

        $user = $tokenRecord->user;
        $request->validate((new ActivateAccountRequest())->rules());

        $user->password = Hash::make($request->password);
        $user->email_verified_at = now();
        $user->save();

        $tokenRecord->used_at = now();
        $tokenRecord->save();

        AccountActivationToken::where('user_id', $user->id)->where('id', '!=', $tokenRecord->id)->whereNull('used_at')->update(['used_at' => now()]);

        return redirect()->route('login')->with('status', 'Votre compte est activé.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::where('email', $request->email)
            ->whereNull('email_verified_at')
            ->firstOrFail();

        AccountActivationToken::where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $newToken = Str::random(64);
        AccountActivationToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $newToken),
            'expires_at' => now()->addHours(48),
            'used_at' => null,
        ]);

        Mail::to($user->email)->send(new AccountActivationMail($newToken));

        return redirect()->route('activation.pending', ['email' => $request->email])
            ->with('status', 'Un nouveau lien d\'activation a été envoyé.');
    }
}