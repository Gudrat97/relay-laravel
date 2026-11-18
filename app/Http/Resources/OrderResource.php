<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'next_statuses' => array_map(fn ($s) => $s->value, $this->status->allowedNext()),
            'customer' => [
                'name' => $this->customer_name,
                'phone' => $this->customer_phone,
            ],
            'pickup_address' => $this->pickup_address,
            'dropoff_address' => $this->dropoff_address,
            'distance_km' => (float) $this->distance_km,
            'price' => [
                'cents' => $this->price_cents,
                'formatted' => '$'.number_format($this->price_cents / 100, 2),
            ],
            'zone' => $this->whenLoaded('zone', fn () => $this->zone?->name),
            'courier' => new CourierResource($this->whenLoaded('courier')),
            'promised_at' => $this->promised_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'on_time' => $this->isOnTime(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
