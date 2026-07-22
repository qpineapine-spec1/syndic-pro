<?php

namespace App\Providers;

use App\View\Composers\HeaderBadgeComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.message-dropdown', [HeaderBadgeComposer::class, 'composeMessages']);
        View::composer('components.notification-dropdown', [HeaderBadgeComposer::class, 'composeNotifications']);
    }
}