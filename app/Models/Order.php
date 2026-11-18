<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'customer_name',
        'customer_phone',
        'pickup_address',
        'dropoff_address',
        'zone_id',
        'courier_id',
        'status',
        'distance_km',
        'price_cents',
        'promised_at',
        'delivered_at',
        'delivery_minutes',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'distance_km' => 'decimal:1',
            'price_cents' => 'integer',
            'delivery_minutes' => 'integer',
            'promised_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->code ??= 'RL-'.Str::upper(Str::random(6));
            $order->status ??= OrderStatus::Pending;
        });
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function scopeDeliveredBetween(Builder $query, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $query
            ->where('status', OrderStatus::Delivered)
            ->whereBetween('delivered_at', [$from, $to]);
    }

    public function isOnTime(): ?bool
    {
        if (! $this->delivered_at || ! $this->promised_at) {
            return null;
        }

        return $this->delivered_at->lessThanOrEqualTo($this->promised_at);
    }
}
