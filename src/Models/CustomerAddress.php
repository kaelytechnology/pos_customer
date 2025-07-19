<?php

namespace Kaely\PosCustomer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class CustomerAddress extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer_addresses';

    protected $fillable = [
        'customer_id',
        'type',
        'street',
        'street_number',
        'interior',
        'neighborhood',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'notes',
        'is_default',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación con el cliente
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relación polimórfica (para compatibilidad)
     */
    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Obtener la dirección completa formateada
     */
    public function getFullAddressAttribute(): string
    {
        $parts = [];

        if ($this->street) {
            $street = $this->street;
            if ($this->street_number) {
                $street .= ' ' . $this->street_number;
            }
            if ($this->interior) {
                $street .= ' Int. ' . $this->interior;
            }
            $parts[] = $street;
        }

        if ($this->neighborhood) {
            $parts[] = $this->neighborhood;
        }

        if ($this->city) {
            $parts[] = $this->city;
        }

        if ($this->state) {
            $parts[] = $this->state;
        }

        if ($this->postal_code) {
            $parts[] = $this->postal_code;
        }

        if ($this->country) {
            $parts[] = $this->country;
        }

        return implode(', ', $parts);
    }

    /**
     * Obtener la dirección corta (sin país)
     */
    public function getShortAddressAttribute(): string
    {
        $parts = [];

        if ($this->street) {
            $street = $this->street;
            if ($this->street_number) {
                $street .= ' ' . $this->street_number;
            }
            if ($this->interior) {
                $street .= ' Int. ' . $this->interior;
            }
            $parts[] = $street;
        }

        if ($this->neighborhood) {
            $parts[] = $this->neighborhood;
        }

        if ($this->city) {
            $parts[] = $this->city;
        }

        if ($this->state) {
            $parts[] = $this->state;
        }

        if ($this->postal_code) {
            $parts[] = $this->postal_code;
        }

        return implode(', ', $parts);
    }

    /**
     * Obtener el tipo de dirección en español
     */
    public function getTypeTextAttribute(): string
    {
        return match($this->type) {
            'billing' => 'Facturación',
            'shipping' => 'Envío',
            default => ucfirst($this->type),
        };
    }

    /**
     * Verificar si es dirección de facturación
     */
    public function getIsBillingAttribute(): bool
    {
        return $this->type === 'billing';
    }

    /**
     * Verificar si es dirección de envío
     */
    public function getIsShippingAttribute(): bool
    {
        return $this->type === 'shipping';
    }

    /**
     * Scope para direcciones de facturación
     */
    public function scopeBilling(Builder $query): Builder
    {
        return $query->where('type', 'billing');
    }

    /**
     * Scope para direcciones de envío
     */
    public function scopeShipping(Builder $query): Builder
    {
        return $query->where('type', 'shipping');
    }

    /**
     * Scope para direcciones por defecto
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope para buscar por ciudad
     */
    public function scopeByCity(Builder $query, string $city): Builder
    {
        return $query->where('city', 'like', "%{$city}%");
    }

    /**
     * Scope para buscar por estado
     */
    public function scopeByState(Builder $query, string $state): Builder
    {
        return $query->where('state', 'like', "%{$state}%");
    }

    /**
     * Scope para buscar por código postal
     */
    public function scopeByPostalCode(Builder $query, string $postalCode): Builder
    {
        return $query->where('postal_code', $postalCode);
    }

    /**
     * Scope para buscar por país
     */
    public function scopeByCountry(Builder $query, string $country): Builder
    {
        return $query->where('country', $country);
    }

    /**
     * Scope para buscar por texto en dirección
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('street', 'like', "%{$search}%")
              ->orWhere('neighborhood', 'like', "%{$search}%")
              ->orWhere('city', 'like', "%{$search}%")
              ->orWhere('state', 'like', "%{$search}%")
              ->orWhere('postal_code', 'like', "%{$search}%");
        });
    }

    /**
     * Obtener información resumida de la dirección
     */
    public function getSummaryAttribute(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_text' => $this->type_text,
            'full_address' => $this->full_address,
            'short_address' => $this->short_address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'phone' => $this->phone,
            'is_default' => $this->is_default,
        ];
    }

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        // Antes de crear
        static::creating(function ($address) {
            if (auth()->check()) {
                $address->created_by = auth()->id();
            }

            // Si es la primera dirección de este tipo, hacerla por defecto
            if ($address->is_default) {
                static::where('customer_id', $address->customer_id)
                      ->where('type', $address->type)
                      ->update(['is_default' => false]);
            }
        });

        // Antes de actualizar
        static::updating(function ($address) {
            if (auth()->check()) {
                $address->updated_by = auth()->id();
            }

            // Si se está marcando como por defecto, quitar el flag de las otras
            if ($address->is_default && $address->isDirty('is_default')) {
                static::where('customer_id', $address->customer_id)
                      ->where('type', $address->type)
                      ->where('id', '!=', $address->id)
                      ->update(['is_default' => false]);
            }
        });

        // Antes de eliminar
        static::deleting(function ($address) {
            if (auth()->check()) {
                $address->deleted_by = auth()->id();
                $address->save();
            }
        });
    }
} 