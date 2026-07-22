<?php

namespace App\Console\Commands;

use App\Mail\PasswordResetMail;
use App\Models\Tenant;
use App\Models\Owner;
use App\Models\Property;
use App\Services\MeetingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendLeaseEndingAlerts extends Command
{
    protected $signature = 'tenants:send-lease-ending-alerts';
    protected $description = 'Send one-time alerts for tenants whose lease ends within 7 days';

    public function handle()
    {
        $now = now();
        $cutoffDate = $now->copy()->addDays(7)->toDateString();
        $tenants = Tenant::where('is_active', true)
            ->whereNotNull('contract_end_date')
            ->where(function ($query) use ($now, $cutoffDate): void {
                $query->where('contract_end_date', '<=', $now->toDateString())
                    ->orWhereBetween('contract_end_date', [$now->toDateString(), $cutoffDate]);
            })
            ->get();

        $meetingService = new MeetingService();

        foreach ($tenants as $tenant) {
            $owner = Owner::find($tenant->owner_id);
            $property = null;
            if ($owner) {
                $property = \App\Models\Property::find($owner->property_id ?? null);
            }

            if ($owner && $property) {
                $message = 'Le contrat du locataire lié à ' . ($owner->user?->name ?? 'un copropriétaire') . ' se termine le ' . $tenant->contract_end_date;
                $meetingService->notifyOwners($property, [$owner], 'Fin de contrat locataire', $message);
                $meetingService->notifySyndic($property, 'Fin de contrat locataire', $message);
            }

            if ($owner && $owner->user) {
                $newPassword = Str::random(12);
                $owner->user->password = Hash::make($newPassword);
                $owner->user->save();
                if ($owner->user->email) {
                    Mail::to($owner->user->email)->send(new PasswordResetMail($newPassword));
                }
            }

            if ($owner) {
                $owner->status = 'proprietaire';
                $owner->save();
            }

            $tenant->is_active = false;
            $tenant->reminder_sent_at = now();
            $tenant->save();
        }

        $this->info('Processed ' . $tenants->count() . ' tenants.');
        return 0;
    }
}