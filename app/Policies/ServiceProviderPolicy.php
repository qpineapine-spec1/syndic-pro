<?php

namespace App\Policies;

use App\Models\ServiceProvider;
use App\Models\User;

class ServiceProviderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'syndic';
    }

    public function view(User $user, ServiceProvider $serviceProvider): bool
    {
        return $user->role === 'syndic' && $serviceProvider->property_id === $user->syndic?->property_id;
    }
}
