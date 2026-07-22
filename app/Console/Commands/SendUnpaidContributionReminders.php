<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Owner;
use App\Models\Property;
use App\Services\MeetingService;
use Illuminate\Console\Command;

class SendUnpaidContributionReminders extends Command
{
    protected $signature = 'contributions:send-unpaid-reminders';
    protected $description = 'Notify owners and the syndic about unpaid cotisations, starting 5 days after the beginning of the month.';

    public function handle()
    {
        $now = now();

        if ($now->day < 5) {
            $this->info('Trop tôt dans le mois, aucune relance envoyée.');
            return 0;
        }

        $currentMonth = $now->format('Y-m');
        $service = new MeetingService();

        $invoices = Invoice::where('status', '!=', 'paid')
            ->where(function ($query) use ($currentMonth) {
                $query->whereNull('unpaid_reminder_sent_at')
                    ->orWhereRaw("DATE_FORMAT(unpaid_reminder_sent_at, '%Y-%m') != ?", [$currentMonth]);
            })
            ->get();

        $count = 0;

        foreach ($invoices as $invoice) {
            $owner = $invoice->owner_id ? Owner::find($invoice->owner_id) : ($invoice->contribution?->owner);
            $property = $invoice->property_id ? Property::find($invoice->property_id) : $owner?->property;

            if (! $owner || ! $property) {
                continue;
            }

            $title = 'Cotisation non payée';
            $message = 'La cotisation / facture ' . ($invoice->invoice_number ?? '#' . $invoice->id) . ' de ' . number_format((float) $invoice->amount, 2) . ' n\'a pas été réglée ce mois-ci.';

            // Notify the owner.
            $service->notifyOwners($property, [$owner], $title, $message);

            // Notify the syndic, naming the owner concerned.
            $service->notifySyndic(
                $property,
                $title,
                'La cotisation de ' . ($owner->user?->name ?? 'un copropriétaire') . ' (facture ' . ($invoice->invoice_number ?? '#' . $invoice->id) . ') reste impayée ce mois-ci.'
            );

            $invoice->unpaid_reminder_sent_at = now();
            $invoice->save();
            $count++;
        }

        $this->info('Relances envoyées pour ' . $count . ' facture(s) impayée(s).');

        return 0;
    }
}