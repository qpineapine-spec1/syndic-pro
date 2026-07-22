<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

class NotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['syndic', 'copropriétaire'], true);
    }

    public function view(User $user, Notification $notification): bool
    {
        if ($notification->user_id === $user->id) {
            return true;
        }

        if ($user->role === 'syndic') {
            return $notification->property_id === $user->syndic?->property_id;
        }

        return $notification->owner_id === $user->owner?->id;
    }
}