<?php

namespace App\Providers;

use App\Services\IndicatorService;
use App\Services\SignalEngine;
use App\Services\NepseScraperService;
use App\Services\AlertService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(IndicatorService::class);
        $this->app->singleton(NepseScraperService::class);
        $this->app->singleton(AlertService::class);
        $this->app->singleton(SignalEngine::class, function ($app) {
            return new SignalEngine($app->make(IndicatorService::class));
        });
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Paginator::defaultView('vendor.pagination.tailwind');

        Event::listen(Registered::class, SendEmailVerificationNotification::class);
    }
}

