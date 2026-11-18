<?php

namespace Database\Seeders;

use App\Enums\CourierStatus;
use App\Enums\OrderStatus;
use App\Enums\Vehicle;
use App\Models\Courier;
use App\Models\User;
use App\Models\Zone;
use App\Services\PricingService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fills the database with 180 days of realistic delivery history so the
 * dashboard has something to show right after `php artisan migrate --seed`.
 */
class DatabaseSeeder extends Seeder
{
    private const DAYS = 180;

    private const ZONES = [
        'Centre' => 0.27,
        'Riverside' => 0.20,
        'North Market' => 0.17,
        'University' => 0.14,
        'Business Park' => 0.13,
        'Airport' => 0.09,
    ];

    public function run(PricingService $pricing): void
    {
        User::factory()->create([
            'name' => 'Dispatch desk',
            'email' => 'dispatch@relay.test',
        ]);

        $zones = collect(self::ZONES)->map(fn ($share, $name) => Zone::create([
            'name' => $name,
            'slug' => Str::slug($name),
        ]));

        $statuses = [
            CourierStatus::Delivering, CourierStatus::Available, CourierStatus::Delivering, CourierStatus::Delayed,
            CourierStatus::Delivering, CourierStatus::Available, CourierStatus::Offline, CourierStatus::Delivering,
            CourierStatus::Available, CourierStatus::Offline, CourierStatus::Delivering, CourierStatus::Delayed,
        ];

        $couriers = collect($statuses)->map(fn ($status) => Courier::factory()->create([
            'status' => $status,
            'vehicle' => fake()->randomElement(Vehicle::cases()),
            'zone_id' => $zones->random()->id,
        ]));

        $this->seedHistory($pricing, $zones->values(), $couriers);
    }

    private function seedHistory(PricingService $pricing, $zones, $couriers): void
    {
        $today = CarbonImmutable::today();
        $weights = array_values(self::ZONES);

        for ($day = self::DAYS - 1; $day >= 0; $day--) {
            $date = $today->subDays($day);
            $weekend = $date->isWeekend();
            $volume = (int) round((40 + (self::DAYS - $day) * 0.12) * ($weekend ? 0.7 : 1) * fake()->randomFloat(2, 0.85, 1.15));
            $rows = [];

            for ($i = 0; $i < $volume; $i++) {
                $distance = fake()->randomFloat(1, 0.8, 14);
                $created = $date->setTime(fake()->numberBetween(8, 21), fake()->numberBetween(0, 59));
                $minutes = fake()->numberBetween(18, 48);
                $promised = $pricing->promisedAt($distance, Vehicle::Scooter, $created);
                $delivered = $created->addMinutes($minutes);

                $rows[] = [
                    'code' => 'RL-'.Str::upper(Str::random(6)),
                    'customer_name' => fake()->name(),
                    'customer_phone' => fake()->numerify('+1 555 ### ####'),
                    'pickup_address' => fake()->streetAddress(),
                    'dropoff_address' => fake()->streetAddress(),
                    'zone_id' => $zones[$this->weightedIndex($weights)]->id,
                    'courier_id' => $couriers->random()->id,
                    'status' => OrderStatus::Delivered->value,
                    'distance_km' => $distance,
                    'price_cents' => $pricing->quoteCents($distance),
                    'promised_at' => $promised,
                    'delivered_at' => $delivered,
                    'delivery_minutes' => $minutes,
                    'created_at' => $created,
                    'updated_at' => $delivered,
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('orders')->insert($chunk);
            }
        }
    }

    /** @param list<float> $weights */
    private function weightedIndex(array $weights): int
    {
        $roll = fake()->randomFloat(4, 0, array_sum($weights));

        foreach ($weights as $index => $weight) {
            if (($roll -= $weight) <= 0) {
                return $index;
            }
        }

        return array_key_last($weights);
    }
}
