<?php

namespace Kaely\PosCustomer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'person_id' => $this->person_id,
            
            // Datos de Person
            'name' => $this->person->name ?? null,
            'email' => $this->person->email ?? null,
            'phone' => $this->person->phone ?? null,
            'birth_date' => $this->person->birth_date?->format('Y-m-d'),
            'gender' => $this->person->gender ?? null,
            
            // Datos de Customer
            'rfc' => $this->rfc,
            'tax_id' => $this->tax_id,
            'customer_group' => $this->customer_group,
            'credit_limit' => $this->credit_limit,
            'credit_limit_formatted' => $this->credit_limit_formatted,
            'points_balance' => $this->points_balance,
            'is_active' => $this->is_active,
            'last_purchase_at' => $this->last_purchase_at?->format('Y-m-d H:i:s'),
            'total_purchases' => $this->total_purchases,
            'total_purchases_formatted' => $this->total_purchases_formatted,
            'total_orders' => $this->total_orders,
            
            // Atributos computados
            'has_available_credit' => $this->has_available_credit,
            'available_credit' => $this->available_credit,
            'has_points' => $this->has_points,
            'valid_points' => $this->valid_points,
            'expired_points' => $this->expired_points,
            
            // Relaciones
            'addresses' => CustomerAddressResource::collection($this->whenLoaded('addresses')),
            'billing_addresses' => CustomerAddressResource::collection($this->whenLoaded('billingAddresses')),
            'shipping_addresses' => CustomerAddressResource::collection($this->whenLoaded('shippingAddresses')),
            'default_billing_address' => new CustomerAddressResource($this->whenLoaded('defaultBillingAddress')),
            'default_shipping_address' => new CustomerAddressResource($this->whenLoaded('defaultShippingAddress')),
            'points_history' => CustomerPointsHistoryResource::collection($this->whenLoaded('pointsHistory')),
            'tickets' => $this->whenLoaded('tickets', function () {
                // Aquí podrías usar un recurso específico para tickets si existe
                return $this->tickets->map(function ($ticket) {
                    return [
                        'id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number ?? null,
                        'total' => $ticket->total ?? null,
                        'created_at' => $ticket->created_at?->format('Y-m-d H:i:s'),
                    ];
                });
            }),
            
            // Estadísticas
            'addresses_count' => $this->when($request->include_counts, $this->addresses()->count()),
            'points_history_count' => $this->when($request->include_counts, $this->pointsHistory()->count()),
            'tickets_count' => $this->when($request->include_counts, $this->tickets()->count()),
            
            // Información resumida
            'summary' => $this->when($request->include_summary, $this->summary),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
} 