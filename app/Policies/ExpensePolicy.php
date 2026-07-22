<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['syndic', 'copropriétaire'], true);
    }

    public function view(User $user, Expense $expense): bool
    {
        if ($user->role === 'syndic') {
            return $expense->property_id === $user->syndic?->property_id;
        }

        return $expense->owner_id === $user->owner?->id;
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->role === 'syndic' && $expense->property_id === $user->syndic?->property_id;
    }
}
