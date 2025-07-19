<?php

namespace Kaely\PosCustomer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Kaely\PosCustomer\Models\CustomerPointsHistory;
use Kaely\PosCustomer\Models\Customer;

class CustomerPointsHistoryFactory extends Factory
{
    protected $model = CustomerPointsHistory::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'type' => $this->faker->randomElement(['earned', 'redeemed', 'expired', 'adjusted']),
            'points' => $this->faker->numberBetween(1, 1000),
            'amount' => $this->faker->optional()->randomFloat(2, 10, 10000),
            'currency' => 'MXN',
            'description' => $this->faker->sentence(),
            'reference_type' => $this->faker->optional()->randomElement(['sale', 'manual', 'expiration']),
            'reference_id' => $this->faker->optional()->numberBetween(1, 1000),
            'expires_at' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
            'is_expired' => false,
        ];
    }

    /**
     * Puntos ganados
     */
    public function earned(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'earned',
            'amount' => $this->faker->randomFloat(2, 10, 10000),
            'reference_type' => 'sale',
            'reference_id' => $this->faker->numberBetween(1, 1000),
            'expires_at' => now()->addDays(365),
            'is_expired' => false,
        ]);
    }

    /**
     * Puntos canjeados
     */
    public function redeemed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'redeemed',
            'points' => -$this->faker->numberBetween(1, 500), // Negativo
            'amount' => null,
            'reference_type' => 'redemption',
            'reference_id' => $this->faker->numberBetween(1, 1000),
            'expires_at' => null,
            'is_expired' => false,
        ]);
    }

    /**
     * Puntos expirados
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expired',
            'points' => -$this->faker->numberBetween(1, 200), // Negativo
            'amount' => null,
            'reference_type' => 'expiration',
            'reference_id' => null,
            'expires_at' => now()->subDays(1),
            'is_expired' => true,
        ]);
    }

    /**
     * Puntos ajustados
     */
    public function adjusted(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'adjusted',
            'amount' => null,
            'reference_type' => 'manual',
            'reference_id' => null,
            'expires_at' => null,
            'is_expired' => false,
        ]);
    }

    /**
     * Puntos que expiran pronto
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'earned',
            'expires_at' => now()->addDays($this->faker->numberBetween(1, 30)),
            'is_expired' => false,
        ]);
    }

    /**
     * Puntos por compra específica
     */
    public function forPurchase(int $saleId, float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'earned',
            'points' => (int) ($amount * config('pos-customer.loyalty.points_per_currency', 1)),
            'amount' => $amount,
            'reference_type' => 'sale',
            'reference_id' => $saleId,
            'expires_at' => now()->addDays(config('pos-customer.loyalty.points_expiration_days', 365)),
            'is_expired' => false,
        ]);
    }

    /**
     * Puntos con descripción específica
     */
    public function withDescription(string $description): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => $description,
        ]);
    }

    /**
     * Puntos con moneda específica
     */
    public function withCurrency(string $currency): static
    {
        return $this->state(fn (array $attributes) => [
            'currency' => $currency,
        ]);
    }
} 