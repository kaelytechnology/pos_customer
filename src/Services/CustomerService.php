<?php

namespace Kaely\PosCustomer\Services;

use Illuminate\Database\Eloquent\Builder;
use Kaely\PosCustomer\Models\Customer;
use Kaelytechnology\AuthPackage\Models\Person;
use Kaely\PosCustomer\Events\CustomerCreated;
use Kaely\PosCustomer\Events\CustomerUpdated;

class CustomerService
{
    /**
     * Buscar clientes con filtros
     */
    public function searchCustomers(array $filters = []): Builder
    {
        $query = Customer::query();

        // Filtros básicos
        if (isset($filters['rfc'])) {
            $query->byRfc($filters['rfc']);
        }

        if (isset($filters['email'])) {
            $query->byEmail($filters['email']);
        }

        if (isset($filters['customer_group'])) {
            $query->byGroup($filters['customer_group']);
        }

        if (isset($filters['is_active'])) {
            if ($filters['is_active']) {
                $query->active();
            } else {
                $query->inactive();
            }
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        // Filtros de crédito
        if (isset($filters['min_credit_limit']) || isset($filters['max_credit_limit'])) {
            $min = $filters['min_credit_limit'] ?? 0;
            $max = $filters['max_credit_limit'] ?? 999999.99;
            $query->byCreditRange($min, $max);
        }

        // Filtros de puntos
        if (isset($filters['min_points']) || isset($filters['max_points'])) {
            $min = $filters['min_points'] ?? 0;
            $max = $filters['max_points'] ?? 999999;
            $query->byPointsRange($min, $max);
        }

        // Filtros de compras totales
        if (isset($filters['min_total_purchases']) || isset($filters['max_total_purchases'])) {
            $min = $filters['min_total_purchases'] ?? 0;
            $max = $filters['max_total_purchases'] ?? 999999.99;
            $query->byTotalPurchasesRange($min, $max);
        }

        // Filtros de fecha de última compra
        if (isset($filters['last_purchase_after'])) {
            $query->purchasedAfter($filters['last_purchase_after']);
        }

        if (isset($filters['last_purchase_before'])) {
            $query->purchasedBefore($filters['last_purchase_before']);
        }

        // Ordenamiento
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDirection = $filters['order_direction'] ?? 'desc';
        
        // Validar campos de ordenamiento
        $allowedOrderFields = [
            'id', 'rfc', 'customer_group', 'credit_limit', 'points_balance',
            'total_purchases', 'total_orders', 'last_purchase_at', 'created_at', 'updated_at'
        ];
        
        if (in_array($orderBy, $allowedOrderFields)) {
            $query->orderBy($orderBy, $orderDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    /**
     * Crear un nuevo cliente
     */
    public function createCustomer(array $data): Customer
    {
        // Crear o actualizar Person
        $personData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'gender' => $data['gender'] ?? null,
        ];

        $person = Person::create($personData);

        // Crear Customer
        $customerData = [
            'person_id' => $person->id,
            'rfc' => $data['rfc'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'customer_group' => $data['customer_group'] ?? config('pos-customer.default_customer_group', 'general'),
            'credit_limit' => $data['credit_limit'] ?? config('pos-customer.default_credit_limit', 0.00),
            'points_balance' => $data['points_balance'] ?? config('pos-customer.default_points_balance', 0),
            'is_active' => $data['is_active'] ?? config('pos-customer.default_active_status', true),
        ];

        $customer = Customer::create($customerData);

        // Crear direcciones si se proporcionan
        if (isset($data['addresses']) && is_array($data['addresses'])) {
            $this->createAddresses($customer, $data['addresses']);
        }

        // Disparar evento
        event(new CustomerCreated($customer));

        return $customer->load(['person', 'addresses']);
    }

    /**
     * Actualizar un cliente
     */
    public function updateCustomer(Customer $customer, array $data): Customer
    {
        // Actualizar Person si se proporcionan datos
        if (isset($data['name']) || isset($data['email']) || isset($data['phone']) || 
            isset($data['birth_date']) || isset($data['gender'])) {
            
            $personData = array_filter([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'gender' => $data['gender'] ?? null,
            ], function ($value) {
                return $value !== null;
            });

            if (!empty($personData)) {
                $customer->person->update($personData);
            }
        }

        // Actualizar Customer
        $customerData = array_filter([
            'rfc' => $data['rfc'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'customer_group' => $data['customer_group'] ?? null,
            'credit_limit' => $data['credit_limit'] ?? null,
            'points_balance' => $data['points_balance'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ], function ($value) {
            return $value !== null;
        });

        if (!empty($customerData)) {
            $customer->update($customerData);
        }

        // Disparar evento
        event(new CustomerUpdated($customer));

        return $customer->load(['person', 'addresses']);
    }

    /**
     * Eliminar un cliente
     */
    public function deleteCustomer(Customer $customer): bool
    {
        return $customer->delete();
    }

    /**
     * Restaurar un cliente eliminado
     */
    public function restoreCustomer(int $id): Customer
    {
        $customer = Customer::withTrashed()->findOrFail($id);
        $customer->restore();
        
        return $customer->load(['person', 'addresses']);
    }

    /**
     * Activar un cliente
     */
    public function activateCustomer(Customer $customer): Customer
    {
        $customer->update(['is_active' => true]);
        
        event(new CustomerUpdated($customer));
        
        return $customer;
    }

    /**
     * Desactivar un cliente
     */
    public function deactivateCustomer(Customer $customer): Customer
    {
        $customer->update(['is_active' => false]);
        
        event(new CustomerUpdated($customer));
        
        return $customer;
    }

    /**
     * Crear direcciones para un cliente
     */
    protected function createAddresses(Customer $customer, array $addresses): void
    {
        foreach ($addresses as $addressData) {
            $addressData['customer_id'] = $customer->id;
            
            // Si es la primera dirección de este tipo, hacerla por defecto
            if (!isset($addressData['is_default'])) {
                $existingAddresses = $customer->addresses()->where('type', $addressData['type'])->count();
                $addressData['is_default'] = $existingAddresses === 0;
            }

            // Si se marca como por defecto, quitar el flag de las otras
            if ($addressData['is_default']) {
                $customer->addresses()
                    ->where('type', $addressData['type'])
                    ->update(['is_default' => false]);
            }

            $customer->addresses()->create($addressData);
        }
    }

    /**
     * Obtener estadísticas de clientes
     */
    public function getStatistics(): array
    {
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::active()->count();
        $inactiveCustomers = Customer::inactive()->count();
        $customersWithCredit = Customer::withCredit()->count();
        $customersWithPoints = Customer::withPoints()->count();

        // Estadísticas por grupo
        $groupStats = Customer::selectRaw('customer_group, COUNT(*) as count')
            ->groupBy('customer_group')
            ->get()
            ->pluck('count', 'customer_group')
            ->toArray();

        // Estadísticas de compras
        $customersWithPurchases = Customer::where('total_orders', '>', 0)->count();
        $totalPurchases = Customer::sum('total_purchases');
        $totalOrders = Customer::sum('total_orders');
        $averagePurchase = $totalOrders > 0 ? $totalPurchases / $totalOrders : 0;

        // Clientes por mes (últimos 12 meses)
        $monthlyStats = Customer::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'period' => $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT),
                    'count' => $item->count,
                ];
            })
            ->toArray();

        return [
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'inactive_customers' => $inactiveCustomers,
            'customers_with_credit' => $customersWithCredit,
            'customers_with_points' => $customersWithPoints,
            'customers_with_purchases' => $customersWithPurchases,
            'total_purchases' => $totalPurchases,
            'total_orders' => $totalOrders,
            'average_purchase' => round($averagePurchase, 2),
            'group_statistics' => $groupStats,
            'monthly_statistics' => $monthlyStats,
            'percentage_active' => $totalCustomers > 0 ? round(($activeCustomers / $totalCustomers) * 100, 2) : 0,
            'percentage_with_credit' => $totalCustomers > 0 ? round(($customersWithCredit / $totalCustomers) * 100, 2) : 0,
            'percentage_with_points' => $totalCustomers > 0 ? round(($customersWithPoints / $totalCustomers) * 100, 2) : 0,
        ];
    }

    /**
     * Actualizar estadísticas de compra de un cliente
     */
    public function updatePurchaseStatistics(Customer $customer, float $amount): void
    {
        $customer->increment('total_orders');
        $customer->increment('total_purchases', $amount);
        $customer->update(['last_purchase_at' => now()]);
    }

    /**
     * Verificar si un cliente puede hacer una compra a crédito
     */
    public function canPurchaseOnCredit(Customer $customer, float $amount): bool
    {
        if (!$customer->is_active) {
            return false;
        }

        if ($customer->credit_limit <= 0) {
            return false;
        }

        // Aquí podrías agregar lógica adicional como verificar historial de pagos
        return true;
    }

    /**
     * Obtener clientes que necesitan seguimiento
     */
    public function getCustomersNeedingFollowUp(): Builder
    {
        return Customer::active()
            ->where('last_purchase_at', '<=', now()->subMonths(3))
            ->where('total_orders', '>', 0)
            ->orderBy('last_purchase_at', 'asc');
    }

    /**
     * Obtener clientes VIP (basado en compras totales)
     */
    public function getVipCustomers(float $threshold = 10000): Builder
    {
        return Customer::active()
            ->where('total_purchases', '>=', $threshold)
            ->orderBy('total_purchases', 'desc');
    }
} 