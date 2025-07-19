<?php

namespace Kaely\PosCustomer\Listeners;

use Illuminate\Support\Facades\Log;
use Kaely\PosCustomer\Events\CustomerCreated;
use Kaely\PosCustomer\Events\CustomerUpdated;
use Kaely\PosCustomer\Events\CustomerAddressCreated;

class LogCustomerActivity
{
    /**
     * Handle customer created event
     */
    public function handleCustomerCreated(CustomerCreated $event): void
    {
        if (!config('pos-customer.audit.enabled', true)) {
            return;
        }

        if (!config('pos-customer.audit.log_activities', true)) {
            return;
        }

        Log::info('Customer created', [
            'customer_id' => $event->customer->id,
            'person_id' => $event->customer->person_id,
            'name' => $event->customer->person->name,
            'email' => $event->customer->person->email,
            'rfc' => $event->customer->rfc,
            'customer_group' => $event->customer->customer_group,
            'created_by' => auth()->id(),
            'created_at' => now()->toISOString(),
        ]);
    }

    /**
     * Handle customer updated event
     */
    public function handleCustomerUpdated(CustomerUpdated $event): void
    {
        if (!config('pos-customer.audit.enabled', true)) {
            return;
        }

        if (!config('pos-customer.audit.log_changes', true)) {
            return;
        }

        $changes = $event->customer->getDirty();
        
        if (empty($changes)) {
            return;
        }

        Log::info('Customer updated', [
            'customer_id' => $event->customer->id,
            'name' => $event->customer->person->name,
            'changes' => $changes,
            'updated_by' => auth()->id(),
            'updated_at' => now()->toISOString(),
        ]);
    }

    /**
     * Handle customer address created event
     */
    public function handleCustomerAddressCreated(CustomerAddressCreated $event): void
    {
        if (!config('pos-customer.audit.enabled', true)) {
            return;
        }

        if (!config('pos-customer.audit.log_activities', true)) {
            return;
        }

        Log::info('Customer address created', [
            'address_id' => $event->address->id,
            'customer_id' => $event->address->customer_id,
            'type' => $event->address->type,
            'city' => $event->address->city,
            'state' => $event->address->state,
            'is_default' => $event->address->is_default,
            'created_by' => auth()->id(),
            'created_at' => now()->toISOString(),
        ]);
    }
} 