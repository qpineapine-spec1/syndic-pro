<?php

namespace App\Policies;

use App\Models\Contribution;
use App\Models\User;

class ContributionPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['syndic', 'copropriétaire'], true);
    }

    public function view(User $user, Contribution $contribution): bool
    {
        if ($user->role === 'syndic') {
            return $contribution->budget->property_id === $user->syndic?->property_id;
        }

        return $contribution->owner_id === $user->owner?->id;
    }

    public function update(User $user, Contribution $contribution): bool
    {
        return $user->role === 'copropriétaire' && $contribution->owner_id === $user->owner?->id;
    }
}
