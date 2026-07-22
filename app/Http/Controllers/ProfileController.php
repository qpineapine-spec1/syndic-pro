<?php

namespace App\Http\Controllers;

use App\Models\Owner;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        
        // Get the owner record for the authenticated user
        $owner = Owner::where('user_id', $user->id)->first();
        
        if (!$owner) {
            abort(404);
        }

        return view('profile.show', [
            'user' => $user,
            'owner' => $owner,
        ]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $owner = Owner::where('user_id', $user->id)->firstOrFail();

        // Validate input
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'lot_surface' => ['required', 'numeric', 'min:0'],
            'surface_confirmation' => ['required', 'numeric', 'min:0'],
            'has_mezzanine' => ['required', 'boolean'],
            'mezzanine_surface' => ['nullable', 'numeric', 'min:0'],
            'office_number' => ['nullable', 'string', 'max:50'],
            'floor' => ['nullable', 'integer'],
            'telephone' => ['nullable', 'string', 'max:20'],
        ];

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);
        
        // Add conditional validation: mezzanine_surface is required if has_mezzanine is true
        $validator->sometimes('mezzanine_surface', ['required', 'numeric', 'min:0'], function ($input) {
            return $input->has_mezzanine == true;
        });
        
        $validated = $validator->validate();

        // Update user info (anti-IDOR: only allow modifying own profile)
        $user->update([
            'name' => $validated['name'],
        ]);

        // Update owner info
        $owner->update([
            'lot_surface' => $validated['lot_surface'],
            'surface_confirmation' => $validated['surface_confirmation'],
            'has_mezzanine' => $validated['has_mezzanine'],
            'mezzanine_surface' => $validated['mezzanine_surface'] ?? null,
            'office_number' => $validated['office_number'] ?? null,
            'floor' => $validated['floor'] ?? null,
            'telephone' => $validated['telephone'] ?? null,
        ]);

        return redirect()->route('profile.show')->with('status', 'Vos informations personnelles ont été mises à jour avec succès.');
    }
}
