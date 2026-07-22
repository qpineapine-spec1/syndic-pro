<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Property;
use App\Services\BudgetPredictionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    public function index(BudgetPredictionService $predictionService)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic') {
            abort(403);
        }

        $property = $user->syndic?->property;
        $budgets = $property ? $property->budgets()->orderByDesc('year')->get() : collect();

        $predictionAvailable = $predictionService->isPredictionAvailable($property);

        return view('budgets.index', compact('budgets', 'predictionAvailable'));
    }

    public function create()
    {
        $user = Auth::user();
        if ($user->role !== 'syndic') {
            abort(403);
        }

        return view('budgets.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic') {
            abort(403);
        }

        $data = $request->validate([
            'year' => 'required|integer',
            'fixed_charges_total' => 'required|numeric',
            'variable_charges_total' => 'required|numeric',
        ]);

        $propertyId = $user->syndic?->property_id;

        $budget = Budget::create([
            'property_id' => $propertyId,
            'syndic_id' => $user->syndic->id,
            'year' => $data['year'],
            'fixed_charges_total' => $data['fixed_charges_total'],
            'variable_charges_total' => $data['variable_charges_total'],
            'is_valid' => false,
        ]);

        activity()
            ->causedBy($user)
            ->performedOn($budget)
            ->withProperties([
                'action_type' => 'budget.create',
                'property_id' => $propertyId,
            ])
            ->log('Budget created');

        return redirect()->route('budgets.index')->with('success', 'Budget enregistré. Il doit être validé manuellement.');
    }

    public function markAsValid(Request $request, Budget $budget)
    {
        $user = Auth::user();
        if ($user->role !== 'syndic') {
            abort(403);
        }

        if ($budget->property_id !== $user->syndic?->property_id) {
            abort(403);
        }

        $budget->is_valid = true;
        $budget->save();

        activity()
            ->causedBy($user)
            ->performedOn($budget)
            ->withProperties([
                'action_type' => 'budget.validate',
                'property_id' => $budget->property_id,
            ])
            ->log('Budget validated');

        return redirect()->route('budgets.index')->with('success', 'Budget validé. Le calcul des cotisations reste manuel.');
    }
}
