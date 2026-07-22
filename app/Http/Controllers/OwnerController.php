<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOwnerRequest;
use App\Mail\AccountActivationMail;
use App\Mail\PasswordResetMail;
use App\Models\AccountActivationToken;
use App\Models\Message;
use App\Models\Owner;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OwnerController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $syndic = $user->syndic;

        if (! $syndic || $user->role !== 'syndic') {
            abort(403);
        }

            $owners = Owner::with(['user', 'tenant'])
            ->where('property_id', $syndic->property_id)
            ->get();

        return view('owners.index', ['owners' => $owners]);
    }

    public function resetPassword(Request $request, Owner $owner)
    {
        $user = auth()->user();
        if ($user->role !== 'syndic' || $user->syndic?->property_id !== $owner->property_id) {
            abort(403);
        }

        $request->validate([
            'new_password' => ['nullable', 'string', 'min:6'],
            'channel' => ['nullable', 'string', 'in:email,internal,all'],
        ]);

        $newPassword = $request->filled('new_password') ? $request->input('new_password') : Str::random(12);
        $owner->user->password = Hash::make($newPassword);
        $owner->user->save();

        $messageText = 'Votre mot de passe a été réinitialisé. Veuillez vous connecter avec le nouveau mot de passe transmis par le syndic.';

        if (in_array($request->input('channel', 'all'), ['email', 'all'], true) && $owner->user?->email) {
            try {
                Mail::to($owner->user->email)->send(new PasswordResetMail($newPassword));
            } catch (\Throwable $e) {
                // keep the password reset functional even if mail delivery fails
            }
        }

        if (in_array($request->input('channel', 'all'), ['internal', 'all'], true) && $owner->property_id) {
            Message::create([
                'property_id' => $owner->property_id,
                'owner_id' => $owner->id,
                'subject' => 'Mot de passe réinitialisé',
                'body' => $messageText,
                'sender_type' => 'syndic',
                'is_read' => false,
            ]);
        }

        return redirect()->back()->with('success', 'Le mot de passe a été mis à jour.');
    }

    public function store(StoreOwnerRequest $request)
    {
        // DEPRECATED: this endpoint previously created User+Owner at invitation time.
        // New flow: use InvitationController@store to create an owner invitation, and
        // complete User+Owner creation during activation (/activate/{token}).
        // Keep this method for backward compatibility / internal usage only.
        return DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make(Str::random(40)),
                'role' => 'copropriétaire',
                'email_verified_at' => null,
            ]);

            $owner = Owner::create([
                'property_id' => $request->property_id,
                'user_id' => $user->id,
                'status' => $request->status,
                'is_tenant' => (bool) $request->is_tenant,
                'lot_surface' => $request->lot_surface,
                'surface_confirmation' => $request->surface_confirmation,
                'has_mezzanine' => (bool) $request->has_mezzanine,
                'mezzanine_surface' => $request->mezzanine_surface,
                'office_number' => $request->office_number,
                'floor' => $request->floor,
                'is_council_member' => (bool) $request->is_council_member,
            ]);

            $token = Str::random(64);
            AccountActivationToken::create([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addHours(48),
            ]);

            Mail::to($user->email)->send(new AccountActivationMail($token));

            return redirect()->back()->with('status', 'Le copropriétaire a été ajouté et un email d’activation a été envoyé.');
        });
    }
}
