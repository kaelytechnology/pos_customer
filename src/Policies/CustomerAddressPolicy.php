<?php

namespace Kaely\PosCustomer\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Kaelytechnology\AuthPackage\Models\User;
use Kaely\PosCustomer\Models\CustomerAddress;

class CustomerAddressPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any customer addresses.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('pos.customer_addresses.view');
    }

    /**
     * Determine whether the user can view the customer address.
     */
    public function view(User $user, CustomerAddress $address): bool
    {
        return $user->can('pos.customer_addresses.view');
    }

    /**
     * Determine whether the user can create customer addresses.
     */
    public function create(User $user): bool
    {
        return $user->can('pos.customer_addresses.create');
    }

    /**
     * Determine whether the user can update the customer address.
     */
    public function update(User $user, CustomerAddress $address): bool
    {
        return $user->can('pos.customer_addresses.update');
    }

    /**
     * Determine whether the user can delete the customer address.
     */
    public function delete(User $user, CustomerAddress $address): bool
    {
        return $user->can('pos.customer_addresses.delete');
    }

    /**
     * Determine whether the user can restore the customer address.
     */
    public function restore(User $user): bool
    {
        return $user->can('pos.customer_addresses.restore');
    }

    /**
     * Determine whether the user can permanently delete the customer address.
     */
    public function forceDelete(User $user, CustomerAddress $address): bool
    {
        return $user->can('pos.customer_addresses.force_delete');
    }

    /**
     * Determine whether the user can set address as default.
     */
    public function setDefault(User $user, CustomerAddress $address): bool
    {
        return $user->can('pos.customer_addresses.set_default');
    }

    /**
     * Determine whether the user can validate address.
     */
    public function validate(User $user, CustomerAddress $address): bool
    {
        return $user->can('pos.customer_addresses.validate');
    }
} 