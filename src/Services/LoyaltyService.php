<?php

namespace Kaely\PosCustomer\Services;

use Kaely\PosCustomer\Models\Customer;
use Kaely\PosCustomer\Models\CustomerPointsHistory;
use Kaely\PosCustomer\Events\PointsEarned;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    /**
     * Calcular puntos basados en el monto de compra
     */
    public function calculatePoints(float $amount, string $currency = 'MXN'): int
    {
        if (!config('pos-customer.loyalty.enabled', true)) {
            return 0;
        }

        $minPurchase = config('pos-customer.loyalty.min_purchase_for_points', 1.00);
        if ($amount < $minPurchase) {
            return 0;
        }

        $pointsPerCurrency = config('pos-customer.loyalty.points_per_currency', 1);
        $maxPointsPerTransaction = config('pos-customer.loyalty.max_points_per_transaction', 1000);

        $points = (int) ($amount * $pointsPerCurrency);
        
        return min($points, $maxPointsPerTransaction);
    }

    /**
     * Otorgar puntos a un cliente
     */
    public function awardPoints(Customer $customer, int $points, float $amount, string $description = '', string $referenceType = null, int $referenceId = null): CustomerPointsHistory
    {
        if (!config('pos-customer.loyalty.enabled', true)) {
            throw new \Exception('Sistema de fidelización deshabilitado');
        }

        if ($points <= 0) {
            throw new \Exception('Los puntos deben ser mayores a 0');
        }

        DB::beginTransaction();

        try {
            // Crear registro en el historial
            $history = CustomerPointsHistory::create([
                'customer_id' => $customer->id,
                'type' => 'earned',
                'points' => $points,
                'amount' => $amount,
                'currency' => config('pos-customer.loyalty.points_currency', 'MXN'),
                'description' => $description ?: "Puntos ganados por compra de {$amount}",
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'expires_at' => now()->addDays(config('pos-customer.loyalty.points_expiration_days', 365)),
            ]);

            // Actualizar balance de puntos del cliente
            $customer->increment('points_balance', $points);

            // Disparar evento
            event(new PointsEarned($customer, $history));

            DB::commit();

            return $history;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Canjear puntos de un cliente
     */
    public function redeemPoints(Customer $customer, int $points, string $description = '', string $referenceType = null, int $referenceId = null): CustomerPointsHistory
    {
        if (!config('pos-customer.loyalty.enabled', true)) {
            throw new \Exception('Sistema de fidelización deshabilitado');
        }

        if ($points <= 0) {
            throw new \Exception('Los puntos deben ser mayores a 0');
        }

        if ($customer->points_balance < $points) {
            throw new \Exception('Puntos insuficientes para el canje');
        }

        DB::beginTransaction();

        try {
            // Crear registro en el historial
            $history = CustomerPointsHistory::create([
                'customer_id' => $customer->id,
                'type' => 'redeemed',
                'points' => -$points, // Negativo para canje
                'amount' => null,
                'currency' => config('pos-customer.loyalty.points_currency', 'MXN'),
                'description' => $description ?: "Puntos canjeados: {$points}",
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            // Actualizar balance de puntos del cliente
            $customer->decrement('points_balance', $points);

            DB::commit();

            return $history;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Ajustar puntos de un cliente (manual)
     */
    public function adjustPoints(Customer $customer, int $points, string $description = '', string $referenceType = null, int $referenceId = null): CustomerPointsHistory
    {
        if (!config('pos-customer.loyalty.enabled', true)) {
            throw new \Exception('Sistema de fidelización deshabilitado');
        }

        DB::beginTransaction();

        try {
            // Crear registro en el historial
            $history = CustomerPointsHistory::create([
                'customer_id' => $customer->id,
                'type' => 'adjusted',
                'points' => $points,
                'amount' => null,
                'currency' => config('pos-customer.loyalty.points_currency', 'MXN'),
                'description' => $description ?: "Ajuste manual de puntos: {$points}",
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            // Actualizar balance de puntos del cliente
            $customer->increment('points_balance', $points);

            DB::commit();

            return $history;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Marcar puntos como expirados
     */
    public function expirePoints(Customer $customer, int $points, string $description = ''): CustomerPointsHistory
    {
        if (!config('pos-customer.loyalty.enabled', true)) {
            throw new \Exception('Sistema de fidelización deshabilitado');
        }

        if ($points <= 0) {
            throw new \Exception('Los puntos deben ser mayores a 0');
        }

        if ($customer->points_balance < $points) {
            throw new \Exception('Puntos insuficientes para expirar');
        }

        DB::beginTransaction();

        try {
            // Crear registro en el historial
            $history = CustomerPointsHistory::create([
                'customer_id' => $customer->id,
                'type' => 'expired',
                'points' => -$points, // Negativo para expiración
                'amount' => null,
                'currency' => config('pos-customer.loyalty.points_currency', 'MXN'),
                'description' => $description ?: "Puntos expirados: {$points}",
            ]);

            // Actualizar balance de puntos del cliente
            $customer->decrement('points_balance', $points);

            DB::commit();

            return $history;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Procesar puntos automáticamente después de una compra
     */
    public function processPurchasePoints(Customer $customer, float $amount, string $referenceType = 'sale', int $referenceId = null): ?CustomerPointsHistory
    {
        if (!config('pos-customer.loyalty.enabled', true)) {
            return null;
        }

        if (!config('pos-customer.integrations.pos_sale.auto_update_points', true)) {
            return null;
        }

        $points = $this->calculatePoints($amount);
        
        if ($points <= 0) {
            return null;
        }

        return $this->awardPoints(
            $customer,
            $points,
            $amount,
            "Puntos ganados por compra #{$referenceId}",
            $referenceType,
            $referenceId
        );
    }

    /**
     * Obtener puntos válidos de un cliente (no expirados)
     */
    public function getValidPoints(Customer $customer): int
    {
        return $customer->valid_points;
    }

    /**
     * Obtener puntos expirados de un cliente
     */
    public function getExpiredPoints(Customer $customer): int
    {
        return $customer->expired_points;
    }

    /**
     * Obtener puntos que expiran pronto
     */
    public function getExpiringSoonPoints(Customer $customer, int $days = 30): int
    {
        return $customer->pointsHistory()
            ->expiringSoon($days)
            ->sum('points');
    }

    /**
     * Obtener historial de puntos de un cliente
     */
    public function getPointsHistory(Customer $customer, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = $customer->pointsHistory();

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['reference_type'])) {
            $query->where('reference_type', $filters['reference_type']);
        }

        if (isset($filters['reference_id'])) {
            $query->where('reference_id', $filters['reference_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Obtener estadísticas de puntos
     */
    public function getPointsStatistics(): array
    {
        $totalPointsAwarded = CustomerPointsHistory::earned()->sum('points');
        $totalPointsRedeemed = abs(CustomerPointsHistory::redeemed()->sum('points'));
        $totalPointsExpired = abs(CustomerPointsHistory::expired()->sum('points'));
        $totalPointsAdjusted = CustomerPointsHistory::adjusted()->sum('points');

        $currentBalance = Customer::sum('points_balance');
        $customersWithPoints = Customer::withPoints()->count();

        // Puntos que expiran en los próximos 30 días
        $expiringSoon = CustomerPointsHistory::expiringSoon(30)->sum('points');

        return [
            'total_points_awarded' => $totalPointsAwarded,
            'total_points_redeemed' => $totalPointsRedeemed,
            'total_points_expired' => $totalPointsExpired,
            'total_points_adjusted' => $totalPointsAdjusted,
            'current_balance' => $currentBalance,
            'customers_with_points' => $customersWithPoints,
            'points_expiring_soon' => $expiringSoon,
            'redemption_rate' => $totalPointsAwarded > 0 ? round(($totalPointsRedeemed / $totalPointsAwarded) * 100, 2) : 0,
            'expiration_rate' => $totalPointsAwarded > 0 ? round(($totalPointsExpired / $totalPointsAwarded) * 100, 2) : 0,
        ];
    }

    /**
     * Procesar expiración automática de puntos
     */
    public function processExpiringPoints(): int
    {
        if (!config('pos-customer.loyalty.enabled', true)) {
            return 0;
        }

        $expiredPoints = CustomerPointsHistory::where('type', 'earned')
            ->where('is_expired', false)
            ->where('expires_at', '<=', now())
            ->get();

        $totalExpired = 0;

        foreach ($expiredPoints as $pointRecord) {
            $customer = $pointRecord->customer;
            
            if ($customer && $customer->points_balance >= $pointRecord->points) {
                $this->expirePoints(
                    $customer,
                    $pointRecord->points,
                    "Puntos expirados automáticamente"
                );
                
                $pointRecord->update(['is_expired' => true]);
                $totalExpired += $pointRecord->points;
            }
        }

        return $totalExpired;
    }

    /**
     * Obtener valor monetario de los puntos
     */
    public function getPointsValue(Customer $customer): float
    {
        $validPoints = $this->getValidPoints($customer);
        $pointsPerCurrency = config('pos-customer.loyalty.points_per_currency', 1);
        
        return $validPoints / $pointsPerCurrency;
    }

    /**
     * Verificar si un cliente puede canjear puntos
     */
    public function canRedeemPoints(Customer $customer, int $points): bool
    {
        if (!$customer->is_active) {
            return false;
        }

        return $customer->points_balance >= $points;
    }
} 