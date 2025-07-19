<?php

namespace Kaely\PosCustomer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'type' => $this->type,
            'type_text' => $this->type_text,
            'street' => $this->street,
            'street_number' => $this->street_number,
            'interior' => $this->interior,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'phone' => $this->phone,
            'notes' => $this->notes,
            'is_default' => $this->is_default,
            
            // Atributos computados
            'full_address' => $this->full_address,
            'short_address' => $this->short_address,
            'is_billing' => $this->is_billing,
            'is_shipping' => $this->is_shipping,
            
            // Información resumida
            'summary' => $this->when($request->include_summary, $this->summary),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
} 