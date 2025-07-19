<?php

namespace Kaely\PosCustomer\Listeners;

use Illuminate\Support\Facades\Notification;
use Kaely\PosCustomer\Events\CustomerCreated;
use Kaely\PosCustomer\Events\CustomerUpdated;
use Kaely\PosCustomer\Events\CustomerAddressCreated;
use Kaely\PosCustomer\Notifications\CustomerCreatedNotification;
use Kaely\PosCustomer\Notifications\CustomerUpdatedNotification;
use Kaely\PosCustomer\Notifications\CustomerAddressCreatedNotification;

class NotifyCustomerChange
{
    /**
     * Handle customer created event
     */
    public function handleCustomerCreated(CustomerCreated $event): void
    {
        if (!config('pos-customer.notifications.enabled', true)) {
            return;
        }

        if (!config('pos-customer.notifications.customer_created', true)) {
            return;
        }

        // Aquí podrías enviar notificaciones a administradores o al cliente
        // Por ejemplo:
        // Notification::route('mail', 'admin@example.com')
        //     ->notify(new CustomerCreatedNotification($event->customer));
    }

    /**
     * Handle customer updated event
     */
    public function handleCustomerUpdated(CustomerUpdated $event): void
    {
        if (!config('pos-customer.notifications.enabled', true)) {
            return;
        }

        if (!config('pos-customer.notifications.customer_updated', true)) {
            return;
        }

        // Aquí podrías enviar notificaciones sobre cambios importantes
        // Por ejemplo, si se cambió el límite de crédito:
        $changes = $event->customer->getDirty();
        
        if (isset($changes['credit_limit'])) {
            // Notificar cambio de límite de crédito
        }

        if (isset($changes['is_active'])) {
            // Notificar cambio de estado
        }
    }

    /**
     * Handle customer address created event
     */
    public function handleCustomerAddressCreated(CustomerAddressCreated $event): void
    {
        if (!config('pos-customer.notifications.enabled', true)) {
            return;
        }

        if (!config('pos-customer.notifications.customer_created', true)) {
            return;
        }

        // Aquí podrías enviar notificaciones sobre nuevas direcciones
        // Por ejemplo, verificar si es una dirección sospechosa
    }
} 