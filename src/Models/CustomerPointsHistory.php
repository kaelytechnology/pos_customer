<?php

namespace Kaely\PosCustomer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class CustomerPointsHistory extends Model
{
    use HasFactory;

    protected $table = 'customer_points_history';

    protected $fillable = [
        'customer_id',
        'type',
        'points',
        'amount',
        'currency',
        'description',
        'reference_type',
        'reference_id',
        'expires_at',
        'is_expired',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'points' => 'integer',
        'amount' => 'decimal:2',
        'is_expired' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con el cliente
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Obtener el tipo de transacción en español
     */
    public function getTypeTextAttribute(): string
    {
        return match($this->type) {
            'earned' => 'Ganados',
            'redeemed' => 'Canjeados',
            'expired' => 'Expirados',
            'adjusted' => 'Ajustados',
            default => ucfirst($this->type),
        };
    }

    /**
     * Verificar si los puntos están expirados
     */
    public function getIsExpiredAttribute(): bool
    {
        if ($this->attributes['is_expired']) {
            return true;
        }

        return $this->expires_at && $this->expires_at <= now();
    }

    /**
     * Obtener días hasta la expiración
     */
    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (!$this->expires_at || $this->is_expired) {
            return null;
        }

        return now()->diffInDays($this->expires_at, false);
    }

    /**
     * Obtener el monto formateado
     */
    public function getAmountFormattedAttribute(): string
    {
        if (!$this->amount) {
            return '0.00';
        }

        return number_format($this->amount, 2);
    }

    /**
     * Obtener el valor de los puntos en moneda
     */
    public function getPointsValueAttribute(): float
    {
        if (!$this->amount || !$this->points) {
            return 0.00;
        }

        return $this->amount / $this->points;
    }

    /**
     * Scope para puntos ganados
     */
    public function scopeEarned(Builder $query): Builder
    {
        return $query->where('type', 'earned');
    }

    /**
     * Scope para puntos canjeados
     */
    public function scopeRedeemed(Builder $query): Builder
    {
        return $query->where('type', 'redeemed');
    }

    /**
     * Scope para puntos expirados
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('type', 'expired');
    }

    /**
     * Scope para puntos ajustados
     */
    public function scopeAdjusted(Builder $query): Builder
    {
        return $query->where('type', 'adjusted');
    }

    /**
     * Scope para puntos válidos (no expirados)
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('is_expired', false)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope para puntos que expiran pronto (30 días)
     */
    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->where('type', 'earned')
                    ->where('is_expired', false)
                    ->where('expires_at', '>', now())
                    ->where('expires_at', '<=', now()->addDays($days));
    }

    /**
     * Scope para buscar por referencia
     */
    public function scopeByReference(Builder $query, string $type, int $id): Builder
    {
        return $query->where('reference_type', $type)
                    ->where('reference_id', $id);
    }

    /**
     * Scope para buscar por rango de fechas
     */
    public function scopeByDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope para buscar por moneda
     */
    public function scopeByCurrency(Builder $query, string $currency): Builder
    {
        return $query->where('currency', $currency);
    }

    /**
     * Scope para puntos por rango de cantidad
     */
    public function scopeByPointsRange(Builder $query, int $min, int $max): Builder
    {
        return $query->whereBetween('points', [$min, $max]);
    }

    /**
     * Scope para puntos por rango de monto
     */
    public function scopeByAmountRange(Builder $query, float $min, float $max): Builder
    {
        return $query->whereBetween('amount', [$min, $max]);
    }

    /**
     * Obtener información resumida del historial
     */
    public function getSummaryAttribute(): array
    {
        return [
            'id' => $this->id,
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
            'days_until_expiration' => $this->days_until_expiration,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        // Antes de crear
        static::creating(function ($history) {
            if (auth()->check()) {
                $history->created_by = auth()->id();
            }

            // Calcular fecha de expiración si no se especifica
            if ($history->type === 'earned' && !$history->expires_at) {
                $expirationDays = config('pos-customer.loyalty.points_expiration_days', 365);
                $history->expires_at = now()->addDays($expirationDays);
            }
        });

        // Antes de actualizar
        static::updating(function ($history) {
            if (auth()->check()) {
                $history->updated_by = auth()->id();
            }

            // Marcar como expirado si la fecha de expiración ya pasó
            if ($history->expires_at && $history->expires_at <= now()) {
                $history->is_expired = true;
            }
        });
    }
} 