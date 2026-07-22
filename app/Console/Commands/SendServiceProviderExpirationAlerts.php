<?php

namespace App\Console\Commands;

use App\Models\ServiceProvider;
use App\Services\MeetingService;
use Illuminate\Console\Command;

class SendServiceProviderExpirationAlerts extends Command
{
    protected $signature = 'providers:send-expiration-alerts';
    protected $description = 'Notify the syndic 30 days (configurable per provider) before a service provider contract ends.';

    public function handle()
    {
        $now = now()->startOfDay();
        $service = new MeetingService();

        $providers = ServiceProvider::where('status', 'active')
            ->whereNotNull('contract_end_date')
            ->whereNull('expiration_alert_sent_at')
            ->get();

        $count = 0;

        foreach ($providers as $provider) {
            $alertDays = $provider->alert_expiration_days ?: 30;
            $alertDate = $provider->contract_end_date->copy()->subDays($alertDays)->startOfDay();

            if ($now->lt($alertDate)) {
                continue;
            }

            $property = $provider->property;
            if (! $property) {
                continue;
            }

            $service->notifySyndic(
                $property,
                'Contrat prestataire bientôt expiré',
                'Le contrat du prestataire ' . $provider->name . ' (' . ($provider->specialty ?? 'prestation') . ') expire le ' . $provider->contract_end_date->format('d/m/Y') . '.'
            );

            $provider->expiration_alert_sent_at = now();
            $provider->save();
            $count++;
        }

        $this->info('Alertes envoyées pour ' . $count . ' prestataire(s).');

        return 0;
    }
}