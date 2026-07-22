<?php

namespace App\Policies;

use App\Models\Vote;
use App\Models\User;

class VotePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['syndic', 'copropriétaire'], true);
    }

    public function view(User $user, Vote $vote): bool
    {
        if ($user->role === 'syndic') {
            return $vote->property_id === $user->syndic?->property_id;
        }

        return $vote->property_id === $user->owner?->property_id;
    }

    public function update(User $user, Vote $vote): bool
    {
        return $user->role === 'syndic' && $vote->property_id === $user->syndic?->property_id;
    }
}
