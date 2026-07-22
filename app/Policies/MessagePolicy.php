<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['syndic', 'copropriétaire'], true);
    }

    public function view(User $user, Message $message): bool
    {
        if ($user->role === 'syndic') {
            return $message->property_id === $user->syndic?->property_id;
        }

        $owner = $user->owner;
        if (! $owner) {
            return false;
        }

        return $message->property_id === $owner->property_id
            && ($message->owner_id === null || $message->owner_id === $owner->id);
    }
}
