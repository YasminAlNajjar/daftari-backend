<?php

namespace App\Providers;

use App\Contracts\PushNotificationSender;
use App\Services\Notifications\FirebasePushNotificationSender;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            PushNotificationSender::class,
            FirebasePushNotificationSender::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) 
        {
            URL::forceScheme('https');
        }
    }
}
