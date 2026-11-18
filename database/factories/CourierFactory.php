<?php

namespace Database\Factories;

use App\Enums\CourierStatus;
use App\Enums\Vehicle;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Courier>
 */
class CourierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->numerify('+1 555 ### ####'),
            'vehicle' => fake()->randomElement(Vehicle::cases()),
            'status' => CourierStatus::Available,
            'rating' => fake()->randomFloat(1, 4.2, 5.0),
            'zone_id' => Zone::factory(),
        ];
    }

    public function status(CourierStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
