<?php

namespace App\Policies;

use App\Models\MeetingRequest;
use App\Models\User;

class MeetingRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['syndic', 'copropriétaire'], true);
    }

    public function view(User $user, MeetingRequest $meetingRequest): bool
    {
        if ($user->role === 'syndic') {
            return $meetingRequest->property_id === $user->syndic?->property_id;
        }

        return $meetingRequest->property_id === $user->owner?->property_id;
    }

    public function update(User $user, MeetingRequest $meetingRequest): bool
    {
        return $user->role === 'syndic' && $meetingRequest->property_id === $user->syndic?->property_id;
    }
}
