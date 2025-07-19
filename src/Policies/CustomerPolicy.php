<?php

namespace Kaely\PosCustomer\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Kaelytechnology\AuthPackage\Models\User;
use Kaely\PosCustomer\Models\Customer;

class CustomerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any customers.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('pos.customers.view');
    }

    /**
     * Determine whether the user can view the customer.
     */
    public function view(User $user, Customer $customer): bool
    {
        return $user->can('pos.customers.view');
    }

    /**
     * Determine whether the user can create customers.
     */
    public function create(User $user): bool
    {
        return $user->can('pos.customers.create');
    }

    /**
     * Determine whether the user can update the customer.
     */
    public function update(User $user, Customer $customer): bool
    {
        return $user->can('pos.customers.update');
    }

    /**
     * Determine whether the user can delete the customer.
     */
    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('pos.customers.delete');
    }

    /**
     * Determine whether the user can restore the customer.
     */
    public function restore(User $user): bool
    {
        return $user->can('pos.customers.restore');
    }

    /**
     * Determine whether the user can permanently delete the customer.
     */
    public function forceDelete(User $user, Customer $customer): bool
    {
        return $user->can('pos.customers.force_delete');
    }

    /**
     * Determine whether the user can view customer statistics.
     */
    public function viewStatistics(User $user): bool
    {
        return $user->can('pos.customers.view_statistics');
    }

    /**
     * Determine whether the user can manage customer credit.
     */
    public function manageCredit(User $user, Customer $customer): bool
    {
        return $user->can('pos.customers.manage_credit');
    }

    /**
     * Determine whether the user can manage customer points.
     */
    public function managePoints(User $user, Customer $customer): bool
    {
        return $user->can('pos.customers.manage_points');
    }

    /**
     * Determine whether the user can view customer purchase history.
     */
    public function viewPurchaseHistory(User $user, Customer $customer): bool
    {
        return $user->can('pos.customers.view_purchase_history');
    }

    /**
     * Determine whether the user can export customer data.
     */
    public function export(User $user): bool
    {
        return $user->can('pos.customers.export');
    }

    /**
     * Determine whether the user can import customer data.
     */
    public function import(User $user): bool
    {
        return $user->can('pos.customers.import');
    }

    /**
     * Determine whether the user can manage customer groups.
     */
    public function manageGroups(User $user): bool
    {
        return $user->can('pos.customers.manage_groups');
    }

    /**
     * Determine whether the user can view customer addresses.
     */
    public function viewAddresses(User $user, Customer $customer): bool
    {
        return $user->can('pos.customers.view_addresses');
    }

    /**
     * Determine whether the user can manage customer addresses.
     */
    public function manageAddresses(User $user, Customer $customer): bool
    {
        return $user->can('pos.customers.manage_addresses');
    }

    /**
     * Determine whether the user can view customer points history.
     */
    public function viewPointsHistory(User $user, Customer $customer): bool
    {
        return $user->can('pos.customers.view_points_history');
    }

    /**
     * Determine whether the user can manage customer points history.
     */
    public function managePointsHistory(User $user, Customer $customer): bool
    {
        return $user->can('pos.customers.manage_points_history');
    }
} 