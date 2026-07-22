<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;

class ComplaintPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['syndic', 'copropriétaire'], true);
    }

    public function view(User $user, Complaint $complaint): bool
    {
        if ($user->role === 'syndic') {
            return $complaint->property_id === $user->syndic?->property_id;
        }

        return $complaint->owner_id === $user->owner?->id;
    }

    public function update(User $user, Complaint $complaint): bool
    {
        return $user->role === 'syndic' && $complaint->property_id === $user->syndic?->property_id;
    }
}
