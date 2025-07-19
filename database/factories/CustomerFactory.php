<?php

namespace Kaely\PosCustomer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kaely\PosCustomer\Models\Customer;
use Kaelytechnology\AuthPackage\Models\Person;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'rfc' => $this->faker->unique()->regexify('[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}'),
            'tax_id' => $this->faker->optional()->numerify('TAX-########'),
            'customer_group' => $this->faker->randomElement(['general', 'vip', 'wholesale', 'retail', 'corporate']),
            'credit_limit' => $this->faker->randomFloat(2, 0, 50000),
            'points_balance' => $this->faker->numberBetween(0, 10000),
            'is_active' => $this->faker->boolean(80), // 80% probabilidad de estar activo
            'last_purchase_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'total_purchases' => $this->faker->randomFloat(2, 0, 100000),
            'total_orders' => $this->faker->numberBetween(0, 500),
        ];
    }

    /**
     * Cliente activo
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Cliente inactivo
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Cliente VIP
     */
    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_group' => 'vip',
            'credit_limit' => $this->faker->randomFloat(2, 10000, 100000),
            'total_purchases' => $this->faker->randomFloat(2, 50000, 500000),
        ]);
    }

    /**
     * Cliente con crédito
     */
    public function withCredit(): static
    {
        return $this->state(fn (array $attributes) => [
            'credit_limit' => $this->faker->randomFloat(2, 1000, 50000),
        ]);
    }

    /**
     * Cliente con puntos
     */
    public function withPoints(): static
    {
        return $this->state(fn (array $attributes) => [
            'points_balance' => $this->faker->numberBetween(100, 10000),
        ]);
    }

    /**
     * Cliente con compras recientes
     */
    public function withRecentPurchases(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_purchase_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'total_purchases' => $this->faker->randomFloat(2, 1000, 50000),
            'total_orders' => $this->faker->numberBetween(1, 100),
        ]);
    }

    /**
     * Cliente sin compras
     */
    public function withoutPurchases(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_purchase_at' => null,
            'total_purchases' => 0,
            'total_orders' => 0,
        ]);
    }

    /**
     * Cliente corporativo
     */
    public function corporate(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_group' => 'corporate',
            'credit_limit' => $this->faker->randomFloat(2, 50000, 200000),
            'total_purchases' => $this->faker->randomFloat(2, 100000, 1000000),
        ]);
    }

    /**
     * Cliente de mayoreo
     */
    public function wholesale(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_group' => 'wholesale',
            'credit_limit' => $this->faker->randomFloat(2, 20000, 100000),
            'total_purchases' => $this->faker->randomFloat(2, 50000, 300000),
        ]);
    }
} 