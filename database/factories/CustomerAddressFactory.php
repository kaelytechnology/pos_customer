<?php

namespace Kaely\PosCustomer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kaely\PosCustomer\Models\CustomerAddress;
use Kaely\PosCustomer\Models\Customer;

class CustomerAddressFactory extends Factory
{
    protected $model = CustomerAddress::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'type' => $this->faker->randomElement(['billing', 'shipping']),
            'street' => $this->faker->streetName(),
            'street_number' => $this->faker->optional()->buildingNumber(),
            'interior' => $this->faker->optional()->bothify('##'),
            'neighborhood' => $this->faker->optional()->citySuffix(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->randomElement(['MX', 'US', 'CA']),
            'phone' => $this->faker->optional()->phoneNumber(),
            'notes' => $this->faker->optional()->sentence(),
            'is_default' => $this->faker->boolean(20), // 20% probabilidad de ser por defecto
        ];
    }

    /**
     * Dirección de facturación
     */
    public function billing(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'billing',
        ]);
    }

    /**
     * Dirección de envío
     */
    public function shipping(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'shipping',
        ]);
    }

    /**
     * Dirección por defecto
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Dirección mexicana
     */
    public function mexican(): static
    {
        return $this->state(fn (array $attributes) => [
            'country' => 'MX',
            'state' => $this->faker->randomElement([
                'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche',
                'Chiapas', 'Chihuahua', 'Coahuila', 'Colima', 'Ciudad de México',
                'Durango', 'Guanajuato', 'Guerrero', 'Hidalgo', 'Jalisco', 'México',
                'Michoacán', 'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca', 'Puebla',
                'Querétaro', 'Quintana Roo', 'San Luis Potosí', 'Sinaloa', 'Sonora',
                'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas'
            ]),
            'postal_code' => $this->faker->numerify('#####'),
        ]);
    }

    /**
     * Dirección estadounidense
     */
    public function american(): static
    {
        return $this->state(fn (array $attributes) => [
            'country' => 'US',
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->numerify('#####'),
        ]);
    }

    /**
     * Dirección canadiense
     */
    public function canadian(): static
    {
        return $this->state(fn (array $attributes) => [
            'country' => 'CA',
            'state' => $this->faker->randomElement([
                'Alberta', 'British Columbia', 'Manitoba', 'New Brunswick',
                'Newfoundland and Labrador', 'Nova Scotia', 'Ontario',
                'Prince Edward Island', 'Quebec', 'Saskatchewan',
                'Northwest Territories', 'Nunavut', 'Yukon'
            ]),
            'postal_code' => $this->faker->regexify('[A-Z][0-9][A-Z] [0-9][A-Z][0-9]'),
        ]);
    }

    /**
     * Dirección completa
     */
    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'street_number' => $this->faker->buildingNumber(),
            'interior' => $this->faker->bothify('##'),
            'neighborhood' => $this->faker->citySuffix(),
            'phone' => $this->faker->phoneNumber(),
            'notes' => $this->faker->sentence(),
        ]);
    }

    /**
     * Dirección mínima
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'street_number' => null,
            'interior' => null,
            'neighborhood' => null,
            'phone' => null,
            'notes' => null,
        ]);
    }
} 