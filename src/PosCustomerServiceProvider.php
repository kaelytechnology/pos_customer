<?php

namespace Kaely\PosCustomer;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;
use Kaely\PosCustomer\Models\Customer;
use Kaely\PosCustomer\Models\CustomerAddress;
use Kaely\PosCustomer\Policies\CustomerPolicy;
use Kaely\PosCustomer\Policies\CustomerAddressPolicy;
use Kaely\PosCustomer\Events\CustomerCreated;
use Kaely\PosCustomer\Events\CustomerUpdated;
use Kaely\PosCustomer\Events\CustomerAddressCreated;
use Kaely\PosCustomer\Events\PointsEarned;
use Kaely\PosCustomer\Listeners\LogCustomerActivity;
use Kaely\PosCustomer\Listeners\NotifyCustomerChange;
use Kaely\PosCustomer\Listeners\UpdateLoyaltyPoints;
use Kaely\PosCustomer\Services\CustomerService;
use Kaely\PosCustomer\Services\LoyaltyService;

class PosCustomerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/pos-customer.php', 'pos-customer'
        );

        // Registrar servicios
        $this->app->singleton(CustomerService::class);
        $this->app->singleton(LoyaltyService::class);
    }

    public function boot(): void
    {
        // Publicar configuración
        $this->publishes([
            __DIR__.'/../config/pos-customer.php' => config_path('pos-customer.php'),
        ], 'pos-customer-config');

        // Publicar migraciones
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'pos-customer-migrations');

        // Publicar factories
        $this->publishes([
            __DIR__.'/../database/factories' => database_path('factories'),
        ], 'pos-customer-factories');

        // Publicar seeders
        $this->publishes([
            __DIR__.'/../database/seeders' => database_path('seeders'),
        ], 'pos-customer-seeders');

        // Cargar migraciones
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Registrar rutas
        $this->registerRoutes();

        // Registrar policies
        $this->registerPolicies();

        // Registrar eventos
        $this->registerEvents();

        // Registrar comandos
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
        }
    }

    protected function registerRoutes(): void
    {
        Route::middleware(['api', 'auth:sanctum'])
            ->prefix(config('pos-customer.api_prefix', 'api/v1/pos/customers'))
            ->group(__DIR__.'/../routes/api.php');
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(CustomerAddress::class, CustomerAddressPolicy::class);
    }

    protected function registerEvents(): void
    {
        Event::listen(CustomerCreated::class, LogCustomerActivity::class);
        Event::listen(CustomerUpdated::class, LogCustomerActivity::class);
        Event::listen(CustomerAddressCreated::class, LogCustomerActivity::class);
        Event::listen(PointsEarned::class, UpdateLoyaltyPoints::class);
        
        Event::listen(CustomerCreated::class, NotifyCustomerChange::class);
        Event::listen(CustomerUpdated::class, NotifyCustomerChange::class);
        Event::listen(CustomerAddressCreated::class, NotifyCustomerChange::class);
    }

    protected function registerCommands(): void
    {
        $this->commands([
            // Comandos futuros si son necesarios
        ]);
    }
} 