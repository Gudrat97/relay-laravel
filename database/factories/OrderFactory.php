<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Zone;
use App\Services\PricingService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $distance = fake()->randomFloat(1, 0.8, 14);
        $pricing = new PricingService;

        return [
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->numerify('+1 555 ### ####'),
            'pickup_address' => fake()->streetAddress(),
            'dropoff_address' => fake()->streetAddress(),
            'zone_id' => Zone::factory(),
            'status' => OrderStatus::Pending,
            'distance_km' => $distance,
            'price_cents' => $pricing->quoteCents($distance),
            'promised_at' => now()->addMinutes(40),
        ];
    }

    /**
     * A finished order. Pass $minutesLate below zero for an early delivery.
     */
    public function delivered(?\DateTimeInterface $at = null, int $minutesLate = -5): static
    {
        return $this->state(function () use ($at, $minutesLate) {
            $deliveredAt = $at ? \Carbon\CarbonImmutable::instance($at) : now()->toImmutable();
            $minutes = fake()->numberBetween(20, 45);

            return [
                'status' => OrderStatus::Delivered,
                'created_at' => $deliveredAt->subMinutes($minutes),
                'promised_at' => $deliveredAt->subMinutes($minutesLate),
                'delivered_at' => $deliveredAt,
                'delivery_minutes' => $minutes,
            ];
        });
    }
}
