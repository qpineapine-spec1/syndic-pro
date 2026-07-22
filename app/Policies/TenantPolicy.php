<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['syndic', 'copropriétaire'], true);
    }

    public function view(User $user, Tenant $tenant): bool
    {
        if ($user->role === 'syndic') {
            return $tenant->owner->property_id === $user->syndic?->property_id;
        }

        return $tenant->owner_id === $user->owner?->id;
    }
}
