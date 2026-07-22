<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\User;

class MeetingPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['syndic', 'copropriétaire'], true);
    }

    public function view(User $user, Meeting $meeting): bool
    {
        if ($user->role === 'syndic') {
            return $meeting->property_id === $user->syndic?->property_id;
        }

        return $meeting->property_id === $user->owner?->property_id;
    }

    public function update(User $user, Meeting $meeting): bool
    {
        return $user->role === 'syndic' && $meeting->property_id === $user->syndic?->property_id;
    }
}
