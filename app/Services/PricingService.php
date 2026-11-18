<?php

namespace App\Services;

use App\Enums\Vehicle;
use Carbon\CarbonImmutable;

final class PricingService
{
    public const BASE_CENTS = 250;

    public const PER_KM_CENTS = 85;

    public const MINIMUM_CENTS = 400;

    /** Minutes for pick-up and hand-over on top of driving time. */
    public const HANDLING_MINUTES = 12;

    public function quoteCents(float $distanceKm): int
    {
        $price = self::BASE_CENTS + self::PER_KM_CENTS * max(0, $distanceKm);

        return max(self::MINIMUM_CENTS, (int) round($price));
    }

    public function promisedAt(float $distanceKm, Vehicle $vehicle = Vehicle::Scooter, ?CarbonImmutable $from = null): CarbonImmutable
    {
        $drivingMinutes = (int) ceil($distanceKm / $vehicle->averageSpeedKmh() * 60);

        return ($from ?? CarbonImmutable::now())->addMinutes($drivingMinutes + self::HANDLING_MINUTES);
    }
}
