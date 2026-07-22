<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule the vote closing reminder command hourly via routes/console
Artisan::command('vote:send-closing-reminders:scheduled', function () {
    $this->call('vote:send-closing-reminders');
})->purpose('Send vote closing reminders')->hourly();

Artisan::command('invoice:send-overdue-reminders:scheduled', function () {
    $this->call('invoice:send-overdue-reminders');
})->purpose('Send overdue invoice reminders')->hourly();

Artisan::command('tenants:send-lease-ending-alerts:scheduled', function () {
    $this->call('tenants:send-lease-ending-alerts');
})->purpose('Send tenant lease ending alerts')->hourly();

Artisan::command('providers:send-expiration-alerts:scheduled', function () {
    $this->call('providers:send-expiration-alerts');
})->purpose('Send service provider contract expiration alerts to the syndic')->daily();

Artisan::command('contributions:send-unpaid-reminders:scheduled', function () {
    $this->call('contributions:send-unpaid-reminders');
})->purpose('Send unpaid cotisation reminders to owners and the syndic')->daily();