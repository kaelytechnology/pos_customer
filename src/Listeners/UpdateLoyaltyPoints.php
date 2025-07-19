<?php

namespace Kaely\PosCustomer\Listeners;

use Kaely\PosCustomer\Events\PointsEarned;
use Illuminate\Support\Facades\Log;

class UpdateLoyaltyPoints
{
    /**
     * Handle points earned event
     */
    public function handle(PointsEarned $event): void
    {
        if (!config('pos-customer.notifications.enabled', true)) {
            return;
        }

        if (!config('pos-customer.notifications.points_earned', true)) {
            return;
        }

        // Aquí podrías enviar notificaciones al cliente sobre puntos ganados
        // Por ejemplo:
        // $event->customer->notify(new PointsEarnedNotification($event->pointsHistory));

        // También podrías actualizar estadísticas o enviar notificaciones push
        Log::info('Points earned', [
            'customer_id' => $event->customer->id,
            'customer_name' => $event->customer->person->name,
            'points' => $event->pointsHistory->points,
            'amount' => $event->pointsHistory->amount,
            'description' => $event->pointsHistory->description,
            'reference_type' => $event->pointsHistory->reference_type,
            'reference_id' => $event->pointsHistory->reference_id,
        ]);
    }
} 