<?php

namespace App\Providers;

use App\Auth\EncryptedEmailUserProvider;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Auth;
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
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        // Register custom user provider for password resets
        Auth::provider('encrypted_email', function ($app, array $config) {
            return new EncryptedEmailUserProvider($app['hash'], $config['model']);
        });
    }
}
