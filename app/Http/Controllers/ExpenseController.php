<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Expense;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    /**
     * Catégories de secours si aucune catégorie de charge variable n'a été
     * déclarée dans le PDF de l'assemblée générale (import non encore fait,
     * ou PDF sans détail des charges variables).
     */
    public const FALLBACK_CATEGORIES = [
        'reparation' => 'Réparation',
        'travaux' => 'Travaux',
        'assurance' => 'Assurance',
        'entretien_espaces_verts' => 'Entretien espaces verts',
        'nettoyage_ponctuel' => 'Nettoyage ponctuel',
        'divers' => 'Divers',
    ];

    public function index()
    {
        $user = Auth::user();
        $property = $this->resolveProperty($user);

        if ($user->role === 'copropriétaire') {
            if (!$user->owner || !$user->owner->is_council_member) {
                abort(403);
            }

            return $this->councilIndex($property);
        }

        $fixedExpenses = collect();
        $variableExpenses = collect();
        $cycleStart = null;
        $latestBudget = null;
        $variableCeiling = null;
        $variableSpent = 0;
        $variableRemaining = null;
        $categories = self::FALLBACK_CATEGORIES;

        if ($property) {
            $fixedExpenses = Expense::where('property_id', $property->id)
                ->where('type', 'fixe')
                ->orderBy('label')
                ->get();

            $cycleStart = $property->currentBillingCycleStart();
            $latestBudget = Budget::where('property_id', $property->id)->latest('id')->first();

            [$variableExpenses, $variableSpent] = $this->currentVariableExpenses($property, $latestBudget);

            $variableCeiling = $latestBudget?->variable_charges_total;
            $variableRemaining = $variableCeiling !== null ? max(0, $variableCeiling - $variableSpent) : null;

            $dynamicCategories = $this->importedVariableCategories($property);
            if ($dynamicCategories->isNotEmpty()) {
                $categories = $dynamicCategories->mapWithKeys(fn ($c) => [$c => $c])->all();
            }
        }

        return view('expenses.index', [
            'fixedExpenses' => $fixedExpenses,
            'variableExpenses' => $variableExpenses,
            'cycleStart' => $cycleStart,
            'categories' => $categories,
            'property' => $property,
            'variableCeiling' => $variableCeiling,
            'variableSpent' => $variableSpent,
            'variableRemaining' => $variableRemaining,
            'variableLimitReached' => $variableCeiling !== null && $variableRemaining <= 0,
        ]);
    }

    /**
     * Création d'une dépense variable (le seul type créable depuis le formulaire "+").
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic') {
            abort(403);
        }

        $property = $user->syndic?->property;
        if (!$property) {
            abort(403);
        }

        $data = $request->validate([
            'categorie' => 'required|string|max:120',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'fichier_facture' => 'nullable|file|max:10240',
        ]);

        $latestBudget = Budget::where('property_id', $property->id)->latest('id')->first();
        [, $alreadySpent] = $this->currentVariableExpenses($property, $latestBudget);
        $ceiling = $latestBudget?->variable_charges_total;

        if ($ceiling !== null) {
            $remaining = max(0, $ceiling - $alreadySpent);
            if ($data['amount'] > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => 'Ce montant dépasse le budget des charges variables fixé par l\'assemblée générale. '
                        . 'Il reste ' . number_format($remaining, 2, ',', ' ') . ' MAD disponibles sur '
                        . number_format($ceiling, 2, ',', ' ') . ' MAD.',
                ]);
            }
        }

        $path = null;
        if ($request->hasFile('fichier_facture')) {
            $path = $request->file('fichier_facture')->store('expenses', 'public');
        }

        Expense::create([
            'property_id' => $property->id,
            'label' => $data['description'] ?: $data['categorie'],
            'categorie' => $data['categorie'],
            'amount' => $data['amount'],
            'expense_date' => now(),
            'type' => 'variable',
            'status' => $path ? 'justificatif_provided' : 'pending',
            'fichier_facture' => $path,
        ]);

        return redirect()->route('expenses.index')->with('success', 'Dépense variable ajoutée.');
    }

    public function uploadReceipt(Request $request, Expense $expense)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic') {
            abort(403);
        }

        if ($expense->property_id !== $user->syndic?->property_id) {
            abort(403);
        }

        $data = $request->validate([
            'fichier_facture' => 'required|file|max:10240',
        ]);

        $path = $request->file('fichier_facture')->store('expenses', 'public');

        $expense->fichier_facture = $path;
        $expense->status = 'justificatif_provided';
        $expense->save();

        return redirect()->route('expenses.index')->with('success', 'Justificatif téléchargé.');
    }

    /**
     * Coche / décoche "payé" pour une dépense fixe, pour le cycle de 30 jours en cours.
     */
    public function togglePaid(Request $request, Expense $expense)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic') {
            abort(403);
        }

        if ($expense->property_id !== $user->syndic?->property_id) {
            abort(403);
        }

        $expense->paid_at = $request->boolean('paid') ? now() : null;
        $expense->save();

        return redirect()->route('expenses.index')->with('success', 'Statut de paiement mis à jour.');
    }

    /**
     * Vue lecture-seule pour un copropriétaire membre du conseil syndical :
     * historique de toutes les dépenses fixes déjà validées (cochées payées) par le
     * syndic, et de toutes les dépenses variables créées par le syndic. Cet historique
     * n'est jamais réinitialisé par le cycle mensuel de facturation : il ne repart à
     * zéro que lorsqu'une nouvelle assemblée générale est importée (ce qui crée un
     * nouveau Budget), comme le fait déjà le reste de l'application pour les dépenses
     * variables du cycle en cours.
     */
    protected function councilIndex(?Property $property)
    {
        $fixedExpenses = collect();
        $variableExpenses = collect();

        if ($property) {
            $fixedExpenses = Expense::where('property_id', $property->id)
                ->where('type', 'fixe')
                ->whereNotNull('paid_at')
                ->orderByDesc('paid_at')
                ->get();

            $variableExpenses = Expense::where('property_id', $property->id)
                ->where('type', 'variable')
                ->where('status', '!=', 'imported')
                ->orderByDesc('expense_date')
                ->get();
        }

        return view('expenses.council', [
            'fixedExpenses' => $fixedExpenses,
            'variableExpenses' => $variableExpenses,
            'property' => $property,
        ]);
    }

    public function downloadFacture(Expense $expense)
    {
        $user = Auth::user();
        $property = $this->resolveProperty($user);

        if (!$property || $expense->property_id !== $property->id) {
            abort(403);
        }

        if ($user->role === 'copropriétaire' && (!$user->owner || !$user->owner->is_council_member)) {
            abort(403);
        }

        if (!$expense->fichier_facture || !\Illuminate\Support\Facades\Storage::disk('public')->exists($expense->fichier_facture)) {
            abort(404);
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->download($expense->fichier_facture);
    }

    protected function resolveProperty($user): ?Property
    {
        if ($user->role === 'syndic') {
            return $user->syndic?->property;
        }

        if ($user->role === 'copropriétaire' && $user->owner) {
            return $user->owner->property;
        }

        abort(403);
    }

    /**
     * Dépenses variables "réelles" (créées par le syndic depuis le formulaire, pas les lignes
     * importées automatiquement depuis le PDF de l'AG qui ne sont que des catégories budgétées),
     * limitées au cycle en cours (depuis le dernier budget/import). Retourne [collection, somme].
     */
    protected function currentVariableExpenses(Property $property, ?Budget $latestBudget): array
    {
        $query = Expense::where('property_id', $property->id)
            ->where('type', 'variable')
            ->where('status', '!=', 'imported');

        if ($latestBudget) {
            $query->where('created_at', '>=', $latestBudget->created_at);
        }

        $expenses = $query->orderByDesc('expense_date')->get();

        return [$expenses, (float) $expenses->sum('amount')];
    }

    /**
     * Catégories de charges variables déclarées dans le dernier PDF d'assemblée générale importé.
     */
    protected function importedVariableCategories(Property $property)
    {
        return Expense::where('property_id', $property->id)
            ->where('type', 'variable')
            ->where('status', 'imported')
            ->whereNotNull('categorie')
            ->distinct()
            ->orderBy('categorie')
            ->pluck('categorie');
    }
}