<?php

namespace App\Providers;

use App\Channels\SmsChannel;
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
        //this is must set config for mail by DB if set in driver table else use the env file
        try {
            (new MailClass())->setConfig();
        } catch (\Exception $e) {
            //skip if there is an error and we will use config use env file if
        }
    }
}
