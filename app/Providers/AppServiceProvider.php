<?php

namespace App\Providers;

use App\Services\IndicatorService;
use App\Services\SignalEngine;
use App\Services\NepseScraperService;
use App\Services\AlertService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
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

        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            return (new MailMessage)
                ->subject('Verify your email — Riwash Money')
                ->greeting('Welcome to Riwash Money!')
                ->line('Thanks for signing up. Please verify your email address to unlock your portfolio, watchlist and live market dashboard.')
                ->action('Verify Email Address', $url)
                ->line('If you did not create an account, no further action is required.');
        });

        ResetPassword::toMailUsing(function ($notifiable, $token) {
            $url = url(route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()], false));

            return (new MailMessage)
                ->subject('Reset your Riwash Money password')
                ->greeting('Forgot your password?')
                ->line('You are receiving this email because we received a password reset request for your account.')
                ->action('Reset Password', $url)
                ->line('This password reset link will expire in ' . config('auth.passwords.'.config('auth.defaults.passwords').'.expire') . ' minutes.')
                ->line('If you did not request a password reset, no further action is required.');
        });
    }
}

