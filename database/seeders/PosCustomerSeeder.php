<?php

namespace Kaely\PosCustomer\Database\Seeders;

use Illuminate\Database\Seeder;
use Kaely\PosCustomer\Models\Customer;
use Kaely\PosCustomer\Models\CustomerAddress;
use Kaely\PosCustomer\Models\CustomerPointsHistory;
use Kaelytechnology\AuthPackage\Models\Person;

class PosCustomerSeeder extends Seeder
{
    public function run(): void
    {
        // Crear clientes de ejemplo
        $this->createSampleCustomers();
        
        // Crear direcciones de ejemplo
        $this->createSampleAddresses();
        
        // Crear historial de puntos de ejemplo
        $this->createSamplePointsHistory();
    }

    protected function createSampleCustomers(): void
    {
        // Cliente VIP
        $vipPerson = Person::create([
            'name' => 'Juan Carlos Pérez',
            'email' => 'juan.perez@empresa.com',
            'phone' => '555-0101',
            'birth_date' => '1985-03-15',
            'gender' => 'male',
        ]);

        Customer::create([
            'person_id' => $vipPerson->id,
            'rfc' => 'PERJ850315ABC',
            'tax_id' => 'TAX-12345678',
            'customer_group' => 'vip',
            'credit_limit' => 50000.00,
            'points_balance' => 2500,
            'is_active' => true,
            'last_purchase_at' => now()->subDays(5),
            'total_purchases' => 125000.00,
            'total_orders' => 45,
        ]);

        // Cliente corporativo
        $corpPerson = Person::create([
            'name' => 'María González',
            'email' => 'maria.gonzalez@corporacion.com',
            'phone' => '555-0202',
            'birth_date' => '1978-07-22',
            'gender' => 'female',
        ]);

        Customer::create([
            'person_id' => $corpPerson->id,
            'rfc' => 'GONM780722DEF',
            'tax_id' => 'TAX-87654321',
            'customer_group' => 'corporate',
            'credit_limit' => 100000.00,
            'points_balance' => 5000,
            'is_active' => true,
            'last_purchase_at' => now()->subDays(2),
            'total_purchases' => 350000.00,
            'total_orders' => 120,
        ]);

        // Cliente de mayoreo
        $wholesalePerson = Person::create([
            'name' => 'Roberto Martínez',
            'email' => 'roberto.martinez@distribuidora.com',
            'phone' => '555-0303',
            'birth_date' => '1982-11-08',
            'gender' => 'male',
        ]);

        Customer::create([
            'person_id' => $wholesalePerson->id,
            'rfc' => 'MARR821108GHI',
            'tax_id' => 'TAX-11223344',
            'customer_group' => 'wholesale',
            'credit_limit' => 75000.00,
            'points_balance' => 1800,
            'is_active' => true,
            'last_purchase_at' => now()->subDays(1),
            'total_purchases' => 200000.00,
            'total_orders' => 85,
        ]);

        // Cliente general
        $generalPerson = Person::create([
            'name' => 'Ana López',
            'email' => 'ana.lopez@email.com',
            'phone' => '555-0404',
            'birth_date' => '1990-05-12',
            'gender' => 'female',
        ]);

        Customer::create([
            'person_id' => $generalPerson->id,
            'rfc' => 'LOPA900512JKL',
            'customer_group' => 'general',
            'credit_limit' => 5000.00,
            'points_balance' => 350,
            'is_active' => true,
            'last_purchase_at' => now()->subDays(15),
            'total_purchases' => 15000.00,
            'total_orders' => 12,
        ]);

        // Cliente inactivo
        $inactivePerson = Person::create([
            'name' => 'Carlos Ruiz',
            'email' => 'carlos.ruiz@email.com',
            'phone' => '555-0505',
            'birth_date' => '1988-09-30',
            'gender' => 'male',
        ]);

        Customer::create([
            'person_id' => $inactivePerson->id,
            'rfc' => 'RUIC880930MNO',
            'customer_group' => 'general',
            'credit_limit' => 0.00,
            'points_balance' => 0,
            'is_active' => false,
            'last_purchase_at' => now()->subMonths(6),
            'total_purchases' => 5000.00,
            'total_orders' => 8,
        ]);

        // Crear algunos clientes adicionales con factory
        Customer::factory(10)->create();
        Customer::factory(5)->vip()->create();
        Customer::factory(3)->corporate()->create();
        Customer::factory(7)->wholesale()->create();
        Customer::factory(5)->inactive()->create();
    }

    protected function createSampleAddresses(): void
    {
        $customers = Customer::all();

        foreach ($customers as $customer) {
            // Dirección de facturación
            CustomerAddress::create([
                'customer_id' => $customer->id,
                'type' => 'billing',
                'street' => 'Av. Insurgentes Sur',
                'street_number' => '1234',
                'interior' => 'A-101',
                'neighborhood' => 'Del Valle',
                'city' => 'Ciudad de México',
                'state' => 'Ciudad de México',
                'postal_code' => '03100',
                'country' => 'MX',
                'phone' => '555-1234',
                'notes' => 'Oficina principal',
                'is_default' => true,
            ]);

            // Dirección de envío
            CustomerAddress::create([
                'customer_id' => $customer->id,
                'type' => 'shipping',
                'street' => 'Calle Reforma',
                'street_number' => '567',
                'neighborhood' => 'Centro',
                'city' => 'Ciudad de México',
                'state' => 'Ciudad de México',
                'postal_code' => '06000',
                'country' => 'MX',
                'phone' => '555-5678',
                'notes' => 'Almacén',
                'is_default' => true,
            ]);

            // Dirección adicional (no por defecto)
            if ($customer->customer_group === 'vip' || $customer->customer_group === 'corporate') {
                CustomerAddress::create([
                    'customer_id' => $customer->id,
                    'type' => 'shipping',
                    'street' => 'Blvd. Miguel de Cervantes',
                    'street_number' => '890',
                    'neighborhood' => 'Polanco',
                    'city' => 'Ciudad de México',
                    'state' => 'Ciudad de México',
                    'postal_code' => '11560',
                    'country' => 'MX',
                    'phone' => '555-9012',
                    'notes' => 'Sucursal Polanco',
                    'is_default' => false,
                ]);
            }
        }

        // Crear algunas direcciones adicionales con factory
        CustomerAddress::factory(20)->create();
        CustomerAddress::factory(10)->billing()->create();
        CustomerAddress::factory(10)->shipping()->create();
        CustomerAddress::factory(5)->mexican()->create();
    }

    protected function createSamplePointsHistory(): void
    {
        $customers = Customer::all();

        foreach ($customers as $customer) {
            // Puntos ganados por compras
            for ($i = 0; $i < rand(3, 10); $i++) {
                $amount = rand(100, 5000);
                $points = (int) ($amount * config('pos-customer.loyalty.points_per_currency', 1));
                
                CustomerPointsHistory::create([
                    'customer_id' => $customer->id,
                    'type' => 'earned',
                    'points' => $points,
                    'amount' => $amount,
                    'currency' => 'MXN',
                    'description' => "Puntos ganados por compra #" . rand(1000, 9999),
                    'reference_type' => 'sale',
                    'reference_id' => rand(1000, 9999),
                    'expires_at' => now()->addDays(365),
                    'is_expired' => false,
                ]);
            }

            // Puntos canjeados
            for ($i = 0; $i < rand(1, 5); $i++) {
                $points = rand(50, 500);
                
                CustomerPointsHistory::create([
                    'customer_id' => $customer->id,
                    'type' => 'redeemed',
                    'points' => -$points,
                    'amount' => null,
                    'currency' => 'MXN',
                    'description' => "Puntos canjeados por descuento",
                    'reference_type' => 'redemption',
                    'reference_id' => rand(1000, 9999),
                    'expires_at' => null,
                    'is_expired' => false,
                ]);
            }

            // Puntos expirados (solo para algunos clientes)
            if (rand(1, 4) === 1) {
                CustomerPointsHistory::create([
                    'customer_id' => $customer->id,
                    'type' => 'expired',
                    'points' => -rand(10, 100),
                    'amount' => null,
                    'currency' => 'MXN',
                    'description' => "Puntos expirados",
                    'reference_type' => 'expiration',
                    'reference_id' => null,
                    'expires_at' => now()->subDays(rand(1, 30)),
                    'is_expired' => true,
                ]);
            }

            // Ajustes manuales (solo para clientes VIP y corporativos)
            if (in_array($customer->customer_group, ['vip', 'corporate'])) {
                CustomerPointsHistory::create([
                    'customer_id' => $customer->id,
                    'type' => 'adjusted',
                    'points' => rand(100, 500),
                    'amount' => null,
                    'currency' => 'MXN',
                    'description' => "Ajuste manual por promoción especial",
                    'reference_type' => 'manual',
                    'reference_id' => null,
                    'expires_at' => null,
                    'is_expired' => false,
                ]);
            }
        }

        // Crear algunos registros adicionales con factory
        CustomerPointsHistory::factory(50)->earned()->create();
        CustomerPointsHistory::factory(20)->redeemed()->create();
        CustomerPointsHistory::factory(10)->expired()->create();
        CustomerPointsHistory::factory(15)->adjusted()->create();
    }
} 