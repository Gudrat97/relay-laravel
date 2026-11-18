<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Courier */
class CourierResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'vehicle' => $this->vehicle->value,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'rating' => (float) $this->rating,
            'zone' => $this->whenLoaded('zone', fn () => $this->zone?->name),
            'deliveries' => $this->whenCounted('deliveries_count'),
            'on_time_rate' => $this->when(
                isset($this->deliveries_count, $this->on_time_count),
                fn () => $this->deliveries_count
                    ? round($this->on_time_count / $this->deliveries_count * 100)
                    : null
            ),
        ];
    }
}
