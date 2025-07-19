<?php

namespace Kaely\PosCustomer\Tests;

use Kaely\PosCustomer\Models\Customer;
use Kaely\PosCustomer\Models\CustomerAddress;
use Kaely\PosCustomer\Models\CustomerPointsHistory;
use Kaelytechnology\AuthPackage\Models\Person;

class CustomerTest extends TestCase
{
    public function test_can_create_customer(): void
    {
        $person = $this->createTestPerson();
        
        $customer = Customer::create([
            'person_id' => $person->id,
            'rfc' => 'TEST123456ABC',
            'tax_id' => 'TAX-12345678',
            'customer_group' => 'vip',
            'credit_limit' => 50000.00,
            'points_balance' => 1000,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals($person->id, $customer->person_id);
        $this->assertEquals('TEST123456ABC', $customer->rfc);
        $this->assertEquals('vip', $customer->customer_group);
        $this->assertEquals(50000.00, $customer->credit_limit);
        $this->assertEquals(1000, $customer->points_balance);
        $this->assertTrue($customer->is_active);
    }

    public function test_customer_has_person_relationship(): void
    {
        $customer = $this->createTestCustomer();
        
        $this->assertInstanceOf(Person::class, $customer->person);
        $this->assertEquals($customer->person_id, $customer->person->id);
    }

    public function test_customer_has_addresses_relationship(): void
    {
        $customer = $this->createTestCustomer();
        $address = $this->createTestAddress(['customer_id' => $customer->id]);
        
        $this->assertTrue($customer->addresses->contains($address));
        $this->assertEquals(1, $customer->addresses->count());
    }

    public function test_customer_has_points_history_relationship(): void
    {
        $customer = $this->createTestCustomer();
        $pointsHistory = $this->createTestPointsHistory(['customer_id' => $customer->id]);
        
        $this->assertTrue($customer->pointsHistory->contains($pointsHistory));
        $this->assertEquals(1, $customer->pointsHistory->count());
    }

    public function test_customer_has_billing_addresses_scope(): void
    {
        $customer = $this->createTestCustomer();
        
        // Crear dirección de facturación
        CustomerAddress::create([
            'customer_id' => $customer->id,
            'type' => 'billing',
            'street' => 'Billing Street',
            'city' => 'Billing City',
            'state' => 'Billing State',
            'postal_code' => '12345',
            'country' => 'MX',
        ]);
        
        // Crear dirección de envío
        CustomerAddress::create([
            'customer_id' => $customer->id,
            'type' => 'shipping',
            'street' => 'Shipping Street',
            'city' => 'Shipping City',
            'state' => 'Shipping State',
            'postal_code' => '54321',
            'country' => 'MX',
        ]);
        
        $this->assertEquals(1, $customer->billingAddresses->count());
        $this->assertEquals('billing', $customer->billingAddresses->first()->type);
    }

    public function test_customer_has_shipping_addresses_scope(): void
    {
        $customer = $this->createTestCustomer();
        
        // Crear dirección de facturación
        CustomerAddress::create([
            'customer_id' => $customer->id,
            'type' => 'billing',
            'street' => 'Billing Street',
            'city' => 'Billing City',
            'state' => 'Billing State',
            'postal_code' => '12345',
            'country' => 'MX',
        ]);
        
        // Crear dirección de envío
        CustomerAddress::create([
            'customer_id' => $customer->id,
            'type' => 'shipping',
            'street' => 'Shipping Street',
            'city' => 'Shipping City',
            'state' => 'Shipping State',
            'postal_code' => '54321',
            'country' => 'MX',
        ]);
        
        $this->assertEquals(1, $customer->shippingAddresses->count());
        $this->assertEquals('shipping', $customer->shippingAddresses->first()->type);
    }

    public function test_customer_has_default_billing_address(): void
    {
        $customer = $this->createTestCustomer();
        
        // Crear dirección de facturación por defecto
        CustomerAddress::create([
            'customer_id' => $customer->id,
            'type' => 'billing',
            'street' => 'Default Billing Street',
            'city' => 'Default Billing City',
            'state' => 'Default Billing State',
            'postal_code' => '12345',
            'country' => 'MX',
            'is_default' => true,
        ]);
        
        $this->assertNotNull($customer->default_billing_address);
        $this->assertTrue($customer->default_billing_address->is_default);
        $this->assertEquals('billing', $customer->default_billing_address->type);
    }

    public function test_customer_has_default_shipping_address(): void
    {
        $customer = $this->createTestCustomer();
        
        // Crear dirección de envío por defecto
        CustomerAddress::create([
            'customer_id' => $customer->id,
            'type' => 'shipping',
            'street' => 'Default Shipping Street',
            'city' => 'Default Shipping City',
            'state' => 'Default Shipping State',
            'postal_code' => '54321',
            'country' => 'MX',
            'is_default' => true,
        ]);
        
        $this->assertNotNull($customer->default_shipping_address);
        $this->assertTrue($customer->default_shipping_address->is_default);
        $this->assertEquals('shipping', $customer->default_shipping_address->type);
    }

    public function test_customer_has_available_credit_attribute(): void
    {
        $customer = $this->createTestCustomer(['credit_limit' => 5000.00]);
        
        $this->assertTrue($customer->has_available_credit);
        $this->assertEquals(5000.00, $customer->available_credit);
    }

    public function test_customer_has_no_available_credit(): void
    {
        $customer = $this->createTestCustomer(['credit_limit' => 0.00]);
        
        $this->assertFalse($customer->has_available_credit);
        $this->assertEquals(0.00, $customer->available_credit);
    }

    public function test_customer_has_points_attribute(): void
    {
        $customer = $this->createTestCustomer(['points_balance' => 500]);
        
        $this->assertTrue($customer->has_points);
    }

    public function test_customer_has_no_points(): void
    {
        $customer = $this->createTestCustomer(['points_balance' => 0]);
        
        $this->assertFalse($customer->has_points);
    }

    public function test_customer_active_scope(): void
    {
        $activeCustomer = $this->createTestCustomer(['is_active' => true]);
        $inactiveCustomer = $this->createTestCustomer(['is_active' => false]);
        
        $activeCustomers = Customer::active()->get();
        $inactiveCustomers = Customer::inactive()->get();
        
        $this->assertTrue($activeCustomers->contains($activeCustomer));
        $this->assertFalse($activeCustomers->contains($inactiveCustomer));
        $this->assertTrue($inactiveCustomers->contains($inactiveCustomer));
        $this->assertFalse($inactiveCustomers->contains($activeCustomer));
    }

    public function test_customer_by_rfc_scope(): void
    {
        $customer = $this->createTestCustomer(['rfc' => 'TEST123456ABC']);
        
        $foundCustomer = Customer::byRfc('TEST123456ABC')->first();
        
        $this->assertEquals($customer->id, $foundCustomer->id);
    }

    public function test_customer_by_group_scope(): void
    {
        $vipCustomer = $this->createTestCustomer(['customer_group' => 'vip']);
        $generalCustomer = $this->createTestCustomer(['customer_group' => 'general']);
        
        $vipCustomers = Customer::byGroup('vip')->get();
        $generalCustomers = Customer::byGroup('general')->get();
        
        $this->assertTrue($vipCustomers->contains($vipCustomer));
        $this->assertFalse($vipCustomers->contains($generalCustomer));
        $this->assertTrue($generalCustomers->contains($generalCustomer));
        $this->assertFalse($generalCustomers->contains($vipCustomer));
    }

    public function test_customer_with_credit_scope(): void
    {
        $customerWithCredit = $this->createTestCustomer(['credit_limit' => 5000.00]);
        $customerWithoutCredit = $this->createTestCustomer(['credit_limit' => 0.00]);
        
        $customersWithCredit = Customer::withCredit()->get();
        
        $this->assertTrue($customersWithCredit->contains($customerWithCredit));
        $this->assertFalse($customersWithCredit->contains($customerWithoutCredit));
    }

    public function test_customer_with_points_scope(): void
    {
        $customerWithPoints = $this->createTestCustomer(['points_balance' => 500]);
        $customerWithoutPoints = $this->createTestCustomer(['points_balance' => 0]);
        
        $customersWithPoints = Customer::withPoints()->get();
        
        $this->assertTrue($customersWithPoints->contains($customerWithPoints));
        $this->assertFalse($customersWithPoints->contains($customerWithoutPoints));
    }

    public function test_customer_search_scope(): void
    {
        $person = $this->createTestPerson(['name' => 'John Doe', 'email' => 'john@example.com']);
        $customer = $this->createTestCustomer([
            'person_id' => $person->id,
            'rfc' => 'DOEJ123456ABC',
            'tax_id' => 'TAX-12345678',
        ]);
        
        // Buscar por nombre
        $foundByName = Customer::search('John')->first();
        $this->assertEquals($customer->id, $foundByName->id);
        
        // Buscar por email
        $foundByEmail = Customer::search('john@example.com')->first();
        $this->assertEquals($customer->id, $foundByEmail->id);
        
        // Buscar por RFC
        $foundByRfc = Customer::search('DOEJ123456ABC')->first();
        $this->assertEquals($customer->id, $foundByRfc->id);
        
        // Buscar por tax_id
        $foundByTaxId = Customer::search('TAX-12345678')->first();
        $this->assertEquals($customer->id, $foundByTaxId->id);
    }

    public function test_customer_formatted_attributes(): void
    {
        $customer = $this->createTestCustomer([
            'credit_limit' => 12345.67,
            'total_purchases' => 98765.43,
        ]);
        
        $this->assertEquals('12,345.67', $customer->credit_limit_formatted);
        $this->assertEquals('98,765.43', $customer->total_purchases_formatted);
    }

    public function test_customer_summary_attribute(): void
    {
        $person = $this->createTestPerson(['name' => 'Test Person', 'email' => 'test@example.com']);
        $customer = $this->createTestCustomer([
            'person_id' => $person->id,
            'rfc' => 'TEST123456ABC',
            'customer_group' => 'vip',
            'credit_limit' => 5000.00,
            'points_balance' => 100,
            'total_purchases' => 15000.00,
            'total_orders' => 25,
        ]);
        
        $summary = $customer->summary;
        
        $this->assertEquals($customer->id, $summary['id']);
        $this->assertEquals('Test Person', $summary['name']);
        $this->assertEquals('test@example.com', $summary['email']);
        $this->assertEquals('TEST123456ABC', $summary['rfc']);
        $this->assertEquals('vip', $summary['customer_group']);
        $this->assertTrue($summary['is_active']);
        $this->assertEquals(5000.00, $summary['credit_limit']);
        $this->assertEquals(100, $summary['points_balance']);
        $this->assertEquals(15000.00, $summary['total_purchases']);
        $this->assertEquals(25, $summary['total_orders']);
    }
} 