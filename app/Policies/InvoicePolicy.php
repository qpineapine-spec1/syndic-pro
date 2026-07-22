<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['syndic', 'copropriétaire'], true);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->role === 'syndic') {
            return $invoice->property_id === $user->syndic?->property_id;
        }

        return $invoice->owner_id === $user->owner?->id;
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->role === 'syndic' && $invoice->property_id === $user->syndic?->property_id;
    }
}
