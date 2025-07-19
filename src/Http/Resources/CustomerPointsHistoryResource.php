<?php

namespace Kaely\PosCustomer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerPointsHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'type' => $this->type,
            'type_text' => $this->type_text,
            'points' => $this->points,
            'amount' => $this->amount,
            'amount_formatted' => $this->amount_formatted,
            'currency' => $this->currency,
            'description' => $this->description,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'expires_at' => $this->expires_at?->format('Y-m-d H:i:s'),
            'is_expired' => $this->is_expired,
            
            // Atributos computados
            'days_until_expiration' => $this->days_until_expiration,
            'points_value' => $this->points_value,
            
            // Información resumida
            'summary' => $this->when($request->include_summary, $this->summary),
            
            // Timestamps
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
} 