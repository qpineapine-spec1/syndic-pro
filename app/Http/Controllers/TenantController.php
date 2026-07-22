<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Owner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'syndic') {
            $property = $user->syndic?->property;

            // Tous les locataires enregistrés chez les bureaux de la copropriété
            $tenants = $property
                ? Tenant::whereHas('owner', function ($q) use ($property) {
                    $q->where('property_id', $property->id);
                })->with(['owner.user'])->orderByDesc('is_active')->orderByDesc('created_at')->get()
                : collect();

            return view('tenants.index', ['tenants' => $tenants]);
        }

        if ($user->role === 'copropriétaire') {
            $owner = $user->owner;
            $tenant = $owner?->tenant;

            return view('tenants.index', compact('tenant', 'owner'));
        }

        abort(403);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'copropriétaire') {
            // Le copropriétaire déclare qu'il loue son bureau à un locataire
            $owner = $user->owner;
            if (! $owner) {
                abort(403);
            }
        } elseif ($user->role === 'syndic' && $user->syndic) {
            $owner = Owner::findOrFail($request->input('owner_id'));
            if ($owner->property_id !== $user->syndic->property_id) {
                abort(403);
            }
        } else {
            abort(403);
        }

        if ($owner->tenant) {
            return response()->json(['message' => 'Un locataire est déjà enregistré pour ce bureau.'], 422);
        }

        $data = $request->validate([
            'tenant_name' => ['required', 'string', 'max:255'],
            'tenant_phone' => ['required', 'string', 'max:255'],
            'contract_start_date' => ['required', 'date'],
            'contract_end_date' => ['required', 'date', 'after:contract_start_date'],
        ]);

        $tenant = Tenant::create([
            'owner_id' => $owner->id,
            'tenant_name' => $data['tenant_name'],
            'tenant_phone' => $data['tenant_phone'],
            'contract_start_date' => $data['contract_start_date'],
            'contract_end_date' => $data['contract_end_date'],
            'is_active' => true,
        ]);

        // Le bureau passe à l'état « En location » tant que le contrat est actif
        $owner->status = 'locataire';
        $owner->save();

        if ($request->wantsJson()) {
            return response()->json($tenant, 201);
        }

        return redirect()->route('tenants.index')->with('status', 'Locataire enregistré avec succès.');
    }

    public function update(Request $request, Tenant $tenant)
    {
        $user = Auth::user();
        $owner = $tenant->owner;

        if ($user->role === 'syndic' && $user->syndic) {
            if (! $owner || $owner->property_id !== $user->syndic->property_id) {
                abort(403);
            }
        } elseif ($user->role === 'copropriétaire') {
            if (! $owner || $owner->id !== $user->owner?->id) {
                abort(403);
            }
        } else {
            abort(403);
        }

        $tenant->tenant_name = $request->input('tenant_name', $tenant->tenant_name);
        $tenant->tenant_phone = $request->input('tenant_phone', $tenant->tenant_phone);
        $tenant->contract_end_date = $request->input('contract_end_date', $tenant->contract_end_date);
        $tenant->is_active = $request->input('is_active', $tenant->is_active);
        $tenant->save();

        // Si le contrat est clôturé manuellement, le bureau redevient "Propriétaire"
        if (! $tenant->is_active && $owner && $owner->status === 'locataire') {
            $owner->status = 'proprietaire';
            $owner->save();
        }

        if ($request->wantsJson()) {
            return response()->json($tenant);
        }

        return redirect()->route('tenants.index')->with('status', 'Locataire mis à jour.');
    }

    public function destroy(Tenant $tenant)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic' || !$user->syndic) {
            abort(403);
        }

        $owner = $tenant->owner;
        if (!$owner || $owner->property_id !== $user->syndic->property_id) {
            abort(403);
        }

        $tenant->delete();

        if ($owner->status === 'locataire') {
            $owner->status = 'proprietaire';
            $owner->save();
        }

        return response()->json([], 200);
    }
}