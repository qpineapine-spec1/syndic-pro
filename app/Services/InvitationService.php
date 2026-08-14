<?php

namespace App\Services;

use App\Models\OwnerInvitation;
use App\Models\User;
use App\Mail\AccountActivationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvitationService
{
    public function createInvitation(array $data, ?int $createdBy = null)
    {
        if (empty($data['email']) && empty($data['phone'])) {
            throw new \InvalidArgumentException('Email or phone is required.');
        }

        if (!empty($data['email']) && User::where('email', $data['email'])->exists()) {
            throw new \RuntimeException('user_exists');
        }

        $resolvedCreatedBy = $createdBy ?? ($data['created_by'] ?? null);
        if ($resolvedCreatedBy === null || !User::whereKey($resolvedCreatedBy)->exists()) {
            $resolvedCreatedBy = null;
        }

        $token = Str::random(64);

        $invitationData = [
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'property_id' => $data['property_id'],
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addHours(48),
        ];

        if ($resolvedCreatedBy !== null) {
            $invitationData['created_by'] = $resolvedCreatedBy;
        }

        $invitation = OwnerInvitation::create($invitationData);

        if ($invitation->email) {
           Mail::to($invitation->email)->queue(new AccountActivationMail($token));
        }

        return ['invitation' => $invitation, 'raw_token' => $token];
    }
}
