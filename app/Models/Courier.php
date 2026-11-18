<?php

namespace App\Models;

use App\Enums\CourierStatus;
use App\Enums\OrderStatus;
use App\Enums\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Courier extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'vehicle', 'status', 'rating', 'zone_id'];

    protected function casts(): array
    {
        return [
            'vehicle' => Vehicle::class,
            'status' => CourierStatus::class,
            'rating' => 'decimal:1',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Adds delivered and on-time counts so a list of couriers needs one query.
     */
    public function scopeWithDeliveryStats(Builder $query): Builder
    {
        return $query->withCount([
            'orders as deliveries_count' => fn (Builder $q) => $q->where('status', OrderStatus::Delivered),
            'orders as on_time_count' => fn (Builder $q) => $q
                ->where('status', OrderStatus::Delivered)
                ->whereColumn('delivered_at', '<=', 'promised_at'),
        ]);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $term
            ? $query->where(fn (Builder $q) => $q
                ->where('name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%"))
            : $query;
    }
}
