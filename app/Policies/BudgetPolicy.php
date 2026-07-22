<?php

namespace App\Policies;

use App\Models\Budget;
use App\Models\User;

class BudgetPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['syndic', 'copropriétaire'], true);
    }

    public function view(User $user, Budget $budget): bool
    {
        if ($user->role === 'syndic') {
            return $budget->property_id === $user->syndic?->property_id;
        }

        return $budget->property_id === $user->owner?->property_id;
    }

    public function update(User $user, Budget $budget): bool
    {
        return $user->role === 'syndic' && $budget->property_id === $user->syndic?->property_id;
    }
}
