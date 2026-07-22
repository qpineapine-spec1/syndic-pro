<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Owner;
use App\Models\Property;
use App\Services\MeetingService;
use Illuminate\Console\Command;

class SendInvoiceOverdueReminders extends Command
{
    protected $signature = 'invoice:send-overdue-reminders';
    protected $description = 'Send internal reminders for overdue invoices that are more than 30 days past due.';

    public function handle()
    {
        $service = new MeetingService();
        $cutoff = now()->subDays(30);

        $invoices = Invoice::whereNull('reminder_sent_at')
            ->where('status', '!=', 'paid')
            ->whereNotNull('issue_date')
            ->where('issue_date', '<', $cutoff)
            ->get();

        foreach ($invoices as $invoice) {
            $property = null;
            if ($invoice->property_id) {
                $property = Property::find($invoice->property_id);
            }

            $owners = collect();
            if ($invoice->owner_id) {
                $owners = Owner::where('id', $invoice->owner_id)->get();
            }

            if ($owners->isEmpty() && $invoice->contribution) {
                $owner = $invoice->contribution->owner;
                if ($owner) {
                    $owners = collect([$owner]);
                    $property = $property ?? $owner->property;
                }
            }

            if ($owners->isEmpty() || !$property) {
                continue;
            }

            $service->notifyOwners(
                $property,
                $owners,
                'Relance facture ' . $invoice->invoice_number,
                'La facture ' . $invoice->invoice_number . ' est impayée depuis plus de 30 jours.'
            );

            $invoice->reminder_sent_at = now();
            $invoice->save();
        }

        return 0;
    }
}
