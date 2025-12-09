<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind service interfaces to implementations based on mode
        $authMode = config('services.authentication.mode', 'monolith');
        $authzMode = config('services.authorization.mode', 'monolith');

        // Authentication Service
        if ($authMode === 'monolith') {
            $this->app->singleton(
                \App\Contracts\Services\AuthenticationServiceInterface::class,
                \App\Services\Local\LocalAuthenticationService::class
            );
        } else {
            $this->app->singleton(
                \App\Contracts\Services\AuthenticationServiceInterface::class,
                \App\Services\Clients\AuthenticationServiceClient::class
            );
        }

        // Authorization Service
        if ($authzMode === 'monolith') {
            $this->app->singleton(
                \App\Contracts\Services\AuthorizationServiceInterface::class,
                \App\Services\Local\LocalAuthorizationService::class
            );
        } else {
            $this->app->singleton(
                \App\Contracts\Services\AuthorizationServiceInterface::class,
                \App\Services\Clients\AuthorizationServiceClient::class
            );
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register event listeners for microservice communication
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\UserCreated::class,
            \App\Listeners\SyncUserToOtherServices::class.'@handleUserCreated'
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\UserUpdated::class,
            \App\Listeners\SyncUserToOtherServices::class.'@handleUserUpdated'
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\UserRoleAssigned::class,
            \App\Listeners\SyncUserToOtherServices::class.'@handleUserRoleAssigned'
        );
    }
}
