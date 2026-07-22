<?php

namespace App\Services;

use App\Models\Property;

class BudgetPredictionService
{
    /**
     * Retourne true si au moins 3 budgets validés consécutifs existent.
     */
    public function isPredictionAvailable(?Property $property): bool
    {
        if (! $property) {
            return false;
        }

        $years = $property->budgets()
            ->where('is_valid', true)
            ->orderByDesc('year')
            ->pluck('year')
            ->unique()
            ->values();

        if ($years->count() < 3) {
            return false;
        }

        // Check consecutive years (descending order)
        $slice = $years->slice(0, 3)->values();
        return ($slice[0] === $slice[1] + 1) && ($slice[1] === $slice[2] + 1);
    }
}
