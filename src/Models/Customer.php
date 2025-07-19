<?php

namespace Kaely\PosCustomer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Kaelytechnology\AuthPackage\Models\Person;
use Kaely\PosSale\Models\Ticket;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customers';

    protected $fillable = [
        'person_id',
        'rfc',
        'tax_id',
        'customer_group',
        'credit_limit',
        'points_balance',
        'is_active',
        'last_purchase_at',
        'total_purchases',
        'total_orders',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'points_balance' => 'integer',
        'is_active' => 'boolean',
        'last_purchase_at' => 'datetime',
        'total_purchases' => 'decimal:2',
        'total_orders' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relación con Person del auth-package
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Relación con direcciones del cliente
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * Relación con direcciones de facturación
     */
    public function billingAddresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)->where('type', 'billing');
    }

    /**
     * Relación con direcciones de envío
     */
    public function shippingAddresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)->where('type', 'shipping');
    }

    /**
     * Relación con el historial de puntos
     */
    public function pointsHistory(): HasMany
    {
        return $this->hasMany(CustomerPointsHistory::class);
    }

    /**
     * Relación con tickets de venta (historial de compras)
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Relación polimórfica con direcciones (para compatibilidad)
     */
    public function addressable(): MorphMany
    {
        return $this->morphMany('Kaely\PosCustomer\Models\CustomerAddress', 'addressable');
    }

    /**
     * Obtener dirección de facturación por defecto
     */
    public function getDefaultBillingAddressAttribute(): ?CustomerAddress
    {
        return $this->billingAddresses()->where('is_default', true)->first();
    }

    /**
     * Obtener dirección de envío por defecto
     */
    public function getDefaultShippingAddressAttribute(): ?CustomerAddress
    {
        return $this->shippingAddresses()->where('is_default', true)->first();
    }

    /**
     * Verificar si el cliente tiene crédito disponible
     */
    public function getHasAvailableCreditAttribute(): bool
    {
        return $this->credit_limit > 0;
    }

    /**
     * Obtener crédito disponible
     */
    public function getAvailableCreditAttribute(): float
    {
        return $this->credit_limit;
    }

    /**
     * Verificar si el cliente tiene puntos disponibles
     */
    public function getHasPointsAttribute(): bool
    {
        return $this->points_balance > 0;
    }

    /**
     * Obtener puntos válidos (no expirados)
     */
    public function getValidPointsAttribute(): int
    {
        return $this->pointsHistory()
            ->where('type', 'earned')
            ->where('is_expired', false)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->sum('points');
    }

    /**
     * Obtener puntos expirados
     */
    public function getExpiredPointsAttribute(): int
    {
        return $this->pointsHistory()
            ->where('type', 'earned')
            ->where('is_expired', true)
            ->sum('points');
    }

    /**
     * Obtener el valor total de compras formateado
     */
    public function getTotalPurchasesFormattedAttribute(): string
    {
        return number_format($this->total_purchases, 2);
    }

    /**
     * Obtener el límite de crédito formateado
     */
    public function getCreditLimitFormattedAttribute(): string
    {
        return number_format($this->credit_limit, 2);
    }

    /**
     * Scope para clientes activos
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para clientes inactivos
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope para buscar por RFC
     */
    public function scopeByRfc(Builder $query, string $rfc): Builder
    {
        return $query->where('rfc', $rfc);
    }

    /**
     * Scope para buscar por grupo de cliente
     */
    public function scopeByGroup(Builder $query, string $group): Builder
    {
        return $query->where('customer_group', $group);
    }

    /**
     * Scope para buscar por email (a través de Person)
     */
    public function scopeByEmail(Builder $query, string $email): Builder
    {
        return $query->whereHas('person', function ($q) use ($email) {
            $q->where('email', $email);
        });
    }

    /**
     * Scope para clientes con crédito disponible
     */
    public function scopeWithCredit(Builder $query): Builder
    {
        return $query->where('credit_limit', '>', 0);
    }

    /**
     * Scope para clientes con puntos
     */
    public function scopeWithPoints(Builder $query): Builder
    {
        return $query->where('points_balance', '>', 0);
    }

    /**
     * Scope para buscar por fecha de última compra
     */
    public function scopeByLastPurchaseDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('last_purchase_at', $date);
    }

    /**
     * Scope para clientes que compraron después de una fecha
     */
    public function scopePurchasedAfter(Builder $query, string $date): Builder
    {
        return $query->where('last_purchase_at', '>=', $date);
    }

    /**
     * Scope para clientes que compraron antes de una fecha
     */
    public function scopePurchasedBefore(Builder $query, string $date): Builder
    {
        return $query->where('last_purchase_at', '<=', $date);
    }

    /**
     * Scope para buscar por nombre, email, RFC o tax_id
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('rfc', 'like', "%{$search}%")
              ->orWhere('tax_id', 'like', "%{$search}%")
              ->orWhereHas('person', function ($personQuery) use ($search) {
                  $personQuery->where('name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
              });
        });
    }

    /**
     * Scope para clientes por rango de crédito
     */
    public function scopeByCreditRange(Builder $query, float $min, float $max): Builder
    {
        return $query->whereBetween('credit_limit', [$min, $max]);
    }

    /**
     * Scope para clientes por rango de puntos
     */
    public function scopeByPointsRange(Builder $query, int $min, int $max): Builder
    {
        return $query->whereBetween('points_balance', [$min, $max]);
    }

    /**
     * Scope para clientes por rango de compras totales
     */
    public function scopeByTotalPurchasesRange(Builder $query, float $min, float $max): Builder
    {
        return $query->whereBetween('total_purchases', [$min, $max]);
    }

    /**
     * Obtener información resumida del cliente
     */
    public function getSummaryAttribute(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->person->name,
            'email' => $this->person->email,
            'rfc' => $this->rfc,
            'customer_group' => $this->customer_group,
            'is_active' => $this->is_active,
            'credit_limit' => $this->credit_limit,
            'points_balance' => $this->points_balance,
            'total_purchases' => $this->total_purchases,
            'total_orders' => $this->total_orders,
            'last_purchase_at' => $this->last_purchase_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        // Antes de crear
        static::creating(function ($customer) {
            if (auth()->check()) {
                $customer->created_by = auth()->id();
            }
        });

        // Antes de actualizar
        static::updating(function ($customer) {
            if (auth()->check()) {
                $customer->updated_by = auth()->id();
            }
        });

        // Antes de eliminar
        static::deleting(function ($customer) {
            if (auth()->check()) {
                $customer->deleted_by = auth()->id();
                $customer->save();
            }
        });
    }
} 