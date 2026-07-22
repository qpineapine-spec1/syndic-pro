<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Contribution;
use App\Models\Invoice;
use App\Models\Owner;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContributionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'syndic') {
            abort(403);
        }

        $syndic = $user->syndic;
        $property = $syndic?->property;

        $owners = Owner::where('property_id', $property?->id ?? 0)->get();

        $contributionsQuery = Contribution::whereHas('owner', function ($query) use ($property) {
            $query->where('property_id', $property?->id ?? 0);
        })->with('owner.user');

        $cycleStart = $property?->currentBillingCycleStart();

        $statusFilter = $request->query('status');
        $contributions = $contributionsQuery->get();

        if ($statusFilter && in_array($statusFilter, ['a_jour', 'en_retard', 'en_attente'], true)) {
            $contributions = $contributions->filter(
                fn ($contribution) => $contribution->computeStatus($cycleStart) === $statusFilter
            )->values();
        }

        $canCalculate = $this->canCalculate($property);
        $incompleteOwners = $this->incompleteOwners($property);
        $unactivatedOwners = $this->unactivatedOwners($property);

        return view('contributions.index', compact(
            'contributions', 'owners', 'canCalculate', 'incompleteOwners', 'unactivatedOwners', 'cycleStart', 'statusFilter'
        ));
    }

    public function ownerContribution()
    {
        $user = Auth::user();
        if ($user->role !== 'copropriétaire' || ! $user->owner) {
            abort(403);
        }

        $owner = $user->owner;
        $budget = Budget::where('property_id', $owner->property_id)
            ->where('is_valid', true)
            ->latest('created_at')
            ->first();

        $contribution = Contribution::where('owner_id', $owner->id)
            ->where('budget_id', $budget?->id)
            ->with('budget')
            ->latest('created_at')
            ->first();

        $cycleStart = $owner->property?->currentBillingCycleStart();
        $dueDate = $cycleStart ? $cycleStart->copy()->addDays(5) : null;
        $contributionStatus = $contribution ? $contribution->computeStatus($cycleStart) : null;
        $unpaidContribution = ($contribution && $contributionStatus !== 'a_jour') ? $contribution : null;

        $paymentHistory = \App\Models\ContributionPayment::where('owner_id', $owner->id)
            ->when($contribution, fn ($query) => $query->where('contribution_id', $contribution->id))
            ->when(!$contribution, fn ($query) => $query->whereRaw('1 = 0'))
            ->orderByDesc('paid_at')
            ->get();

        return view('contributions.owner', compact(
            'contribution', 'unpaidContribution', 'cycleStart', 'dueDate', 'contributionStatus', 'paymentHistory'
        ));
    }

    public function calculate(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'syndic') {
            abort(403);
        }

        $property = Property::findOrFail($request->input('property_id'));

        if ($property->id !== $user->syndic?->property_id) {
            abort(403);
        }

        $incompleteOwners = $this->incompleteOwners($property);
        if ($incompleteOwners->isNotEmpty()) {
            $message = 'Superficie manquante : ' . $incompleteOwners->count() . ' copropriétaire(s) sur ' . Owner::where('property_id', $property->id)->count() . ' n\'ont pas encore de superficie renseignée. ';
            $message .= implode(', ', $incompleteOwners->pluck('user.name')->toArray());

            return redirect()->route('contributions.index')->with('error', $message);
        }

        $unactivatedOwners = $this->unactivatedOwners($property);
        if ($unactivatedOwners->isNotEmpty()) {
            $message = 'Comptes non activés : ' . $unactivatedOwners->count() . ' copropriétaire(s) n\'ont pas encore activé leur compte. ';
            $message .= implode(', ', $unactivatedOwners->pluck('user.name')->toArray());

            return redirect()->route('contributions.index')->with('error', $message);
        }

        $budget = Budget::where('property_id', $property->id)
            ->where('is_valid', true)
            ->latest('created_at')
            ->first();

        if (! $budget) {
            return redirect()->route('contributions.index')->with('error', 'Aucun budget validé, impossible de calculer les cotisations');
        }

        $owners = Owner::where('property_id', $property->id)->get();
        if ($owners->isEmpty()) {
            return redirect()->route('contributions.index')->with('error', 'Aucun copropriétaire n’est associé à cette copropriété.');
        }

        $totalSurface = $owners->sum('lot_surface');

        foreach ($owners as $owner) {
            $surface = (float) ($owner->lot_surface ?? 0);
            $tantieme = $totalSurface > 0 ? ($surface / $totalSurface) * 1000 : 0;
            $montantAnnuel = ($budget->fixed_charges_total + $budget->variable_charges_total) * ($tantieme / 1000);
            $montantMensuel = $montantAnnuel / 12;

            Contribution::updateOrCreate(
                [
                    'owner_id' => $owner->id,
                    'budget_id' => $budget->id,
                ],
                [
                    'tantieme' => $tantieme,
                    'quote_part_terrain' => 0,
                    'montant_annuel' => $montantAnnuel,
                    'montant_mensuel' => $montantMensuel,
                    'charges_surplus' => 0,
                    'status' => 'a_jour',
                ]
            );
        }

        return redirect()->route('contributions.index')->with('success', 'Les cotisations ont été calculées avec succès.');
    }

    /**
     * Coche / décoche "payé" pour une cotisation, pour le cycle de 30 jours en cours.
     */
    public function togglePaid(Request $request, Contribution $contribution)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic') {
            abort(403);
        }

        if ($contribution->owner->property_id !== $user->syndic?->property_id) {
            abort(403);
        }

        $paid = $request->boolean('paid');
        $contribution->paid_at = $paid ? now() : null;
        $contribution->save();

        $cycleStart = $contribution->owner->property?->currentBillingCycleStart();

        if ($paid) {
            $alreadyLogged = $cycleStart
                ? \App\Models\ContributionPayment::where('contribution_id', $contribution->id)
                    ->where('paid_at', '>=', $cycleStart)
                    ->exists()
                : false;

            if (!$alreadyLogged) {
                \App\Models\ContributionPayment::create([
                    'contribution_id' => $contribution->id,
                    'owner_id' => $contribution->owner_id,
                    'amount' => $contribution->montant_mensuel,
                    'paid_at' => now(),
                ]);
            }
        } elseif ($cycleStart) {
            // Décoche = correction d'une erreur pour le cycle en cours uniquement.
            // L'historique des cycles précédents n'est jamais supprimé.
            \App\Models\ContributionPayment::where('contribution_id', $contribution->id)
                ->where('paid_at', '>=', $cycleStart)
                ->delete();
        }

        return redirect()->route('contributions.index')->with('success', 'Statut de paiement mis à jour.');
    }

    /**
     * Ajoute une charge surplus, divisée à parts égales entre tous les copropriétaires
     * ayant une cotisation calculée sur le budget en cours.
     */
    public function addSurplus(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic') {
            abort(403);
        }

        $property = $user->syndic?->property;
        if (! $property) {
            abort(403);
        }

        $data = $request->validate([
            'surplus_amount' => 'required|numeric|min:0.01',
        ]);

        $budget = Budget::where('property_id', $property->id)
            ->where('is_valid', true)
            ->latest('created_at')
            ->first();

        if (! $budget) {
            return redirect()->route('contributions.index')->with('error', 'Aucun budget validé : impossible de répartir la charge surplus.');
        }

        $contributions = Contribution::where('budget_id', $budget->id)->get();
        if ($contributions->isEmpty()) {
            return redirect()->route('contributions.index')->with('error', 'Aucune cotisation calculée : impossible de répartir la charge surplus.');
        }

        $share = round($data['surplus_amount'] / $contributions->count(), 2);

        foreach ($contributions as $contribution) {
            $contribution->increment('charges_surplus', $share);
        }

        return redirect()->route('contributions.index')->with('success', 'Charge surplus de ' . number_format($data['surplus_amount'], 2, ',', ' ') . ' MAD répartie sur ' . $contributions->count() . ' copropriétaire(s).');
    }

    public function canCalculate(?Property $property): bool
    {
        if (! $property) {
            return false;
        }

        // BR-01 : le tantième est calculé comme lot_surface / surface_totale_immeuble.
        // Si une seule surface est absente, la somme des tantièmes ne fait plus 1000/1000 et
        // la cotisation de tous les copropriétaires devient fausse. On bloque donc le calcul.
        // ALSO: tous les comptes doivent être activés (email_verified_at).
        $owners = Owner::where('property_id', $property->id)->get();

        if ($owners->count() === 0) {
            return false;
        }

        return $this->incompleteOwners($property)->isEmpty() &&
               $this->unactivatedOwners($property)->isEmpty() &&
               Budget::where('property_id', $property->id)
                   ->where('is_valid', true)
                   ->exists();
    }

    protected function incompleteOwners(?Property $property)
    {
        if (! $property) {
            return collect();
        }

        return Owner::where('property_id', $property->id)
            ->where(function ($query) {
                $query->whereNull('lot_surface')->orWhere('lot_surface', '<=', 0);
            })
            ->with('user')
            ->get();
    }

    protected function unactivatedOwners(?Property $property)
    {
        if (! $property) {
            return collect();
        }

        return Owner::where('property_id', $property->id)
            ->whereHas('user', function ($query) {
                $query->whereNull('email_verified_at');
            })
            ->with('user')
            ->get();
    }
}