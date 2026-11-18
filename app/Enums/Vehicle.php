<?php

namespace App\Enums;

enum Vehicle: string
{
    case Bicycle = 'bicycle';
    case Scooter = 'scooter';
    case Car = 'car';
    case Van = 'van';

    /** Average city speed used for delivery time estimates. */
    public function averageSpeedKmh(): int
    {
        return match ($this) {
            self::Bicycle => 14,
            self::Scooter => 22,
            self::Car, self::Van => 26,
        };
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
