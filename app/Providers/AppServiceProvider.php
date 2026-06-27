<?php

namespace App\Providers;

use App\Channels\SmsChannel;
use App\Channels\WhatsAppChannel;
use App\Http\Controllers\HelperClass\MailClass;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        if (!$this->app->environment('production') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Notification::extend('sms', function ($app) {
            return new SmsChannel();
        });

        Notification::extend('whatsapp', function ($app) {
            return new WhatsAppChannel();
        });

        // Defer mail config loading to avoid database query during bootstrap
        // This will only run when mail is actually needed
        $this->app->resolving('mailer', function () {
            try {
                (new MailClass())->setConfig();
            } catch (\Exception $e) {
                //skip if there is an error and we will use config from env file
            }
        });
    }
}
