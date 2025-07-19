<?php

namespace Kaely\PosCustomer\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Kaely\PosCustomer\PosCustomerServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            PosCustomerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Configuración de base de datos para testing
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configuración del paquete para testing
        $app['config']->set('pos-customer', [
            'api_prefix' => 'api/v1/pos/customers',
            'default_active_status' => true,
            'require_rfc' => false,
            'require_tax_id' => false,
            'default_customer_group' => 'general',
            'default_credit_limit' => 0.00,
            'default_points_balance' => 0,
            'credit' => [
                'enabled' => true,
                'max_credit_limit' => 100000.00,
                'min_credit_limit' => 0.00,
                'credit_precision' => 2,
                'auto_approve_limit' => 1000.00,
            ],
            'loyalty' => [
                'enabled' => true,
                'points_per_currency' => 1,
                'points_currency' => 'MXN',
                'min_purchase_for_points' => 1.00,
                'points_expiration_days' => 365,
                'max_points_per_transaction' => 1000,
            ],
            'addresses' => [
                'max_addresses_per_customer' => 5,
                'require_phone' => false,
                'require_postal_code' => true,
                'default_country' => 'MX',
            ],
            'pagination' => [
                'default_per_page' => 15,
                'max_per_page' => 100,
                'per_page_options' => [10, 15, 25, 50, 100],
            ],
            'notifications' => [
                'enabled' => false, // Deshabilitar para testing
                'customer_created' => false,
                'customer_updated' => false,
                'points_earned' => false,
                'credit_limit_exceeded' => false,
                'channels' => ['mail', 'database'],
            ],
            'audit' => [
                'enabled' => false, // Deshabilitar para testing
                'log_activities' => false,
                'log_changes' => false,
                'retention_days' => 365,
            ],
            'cache' => [
                'enabled' => false, // Deshabilitar para testing
                'ttl' => 3600,
                'prefix' => 'pos_customer',
            ],
            'search' => [
                'min_length' => 2,
                'max_results' => 50,
                'fuzzy_search' => true,
                'search_fields' => ['name', 'email', 'rfc', 'tax_id'],
            ],
            'integrations' => [
                'auth_package' => [
                    'enabled' => true,
                    'extend_person' => true,
                ],
                'pos_sale' => [
                    'enabled' => true,
                    'auto_update_points' => true,
                    'auto_update_purchase_history' => true,
                ],
            ],
        ]);

        // Configuración de autenticación para testing
        $app['config']->set('auth.defaults.guard', 'sanctum');
        $app['config']->set('auth.guards.sanctum', [
            'driver' => 'sanctum',
            'provider' => 'users',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Ejecutar migraciones
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        
        // Ejecutar migraciones del auth-package si existen
        if (class_exists('Kaelytechnology\AuthPackage\Database\Migrations\CreatePersonsTable')) {
            $this->loadMigrationsFrom(database_path('migrations'));
        }

        // Crear tablas necesarias para testing
        $this->createTestTables();
    }

    protected function createTestTables(): void
    {
        // Crear tabla users si no existe
        if (!Schema::hasTable('users')) {
            Schema::create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Crear tabla persons si no existe (para auth-package)
        if (!Schema::hasTable('persons')) {
            Schema::create('persons', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->date('birth_date')->nullable();
                $table->enum('gender', ['male', 'female', 'other'])->nullable();
                $table->timestamps();
            });
        }
    }

    protected function createTestUser(array $attributes = []): \App\Models\User
    {
        return \App\Models\User::factory()->create($attributes);
    }

    protected function createTestPerson(array $attributes = []): \Kaelytechnology\AuthPackage\Models\Person
    {
        return \Kaelytechnology\AuthPackage\Models\Person::create(array_merge([
            'name' => 'Test Person',
            'email' => 'test@example.com',
        ], $attributes));
    }

    protected function createTestCustomer(array $attributes = []): \Kaely\PosCustomer\Models\Customer
    {
        $person = $this->createTestPerson();
        
        return \Kaely\PosCustomer\Models\Customer::create(array_merge([
            'person_id' => $person->id,
            'rfc' => 'TEST123456ABC',
            'customer_group' => 'general',
            'credit_limit' => 1000.00,
            'points_balance' => 0,
            'is_active' => true,
        ], $attributes));
    }

    protected function createTestAddress(array $attributes = []): \Kaely\PosCustomer\Models\CustomerAddress
    {
        $customer = $this->createTestCustomer();
        
        return \Kaely\PosCustomer\Models\CustomerAddress::create(array_merge([
            'customer_id' => $customer->id,
            'type' => 'billing',
            'street' => 'Test Street',
            'city' => 'Test City',
            'state' => 'Test State',
            'postal_code' => '12345',
            'country' => 'MX',
            'is_default' => true,
        ], $attributes));
    }

    protected function createTestPointsHistory(array $attributes = []): \Kaely\PosCustomer\Models\CustomerPointsHistory
    {
        $customer = $this->createTestCustomer();
        
        return \Kaely\PosCustomer\Models\CustomerPointsHistory::create(array_merge([
            'customer_id' => $customer->id,
            'type' => 'earned',
            'points' => 100,
            'amount' => 100.00,
            'currency' => 'MXN',
            'description' => 'Test points',
            'expires_at' => now()->addDays(365),
            'is_expired' => false,
        ], $attributes));
    }
} 