<?php

namespace App\Enums;

enum CourierStatus: string
{
    case Available = 'available';
    case Delivering = 'delivering';
    case Delayed = 'delayed';
    case Offline = 'offline';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function canTakeOrders(): bool
    {
        return $this === self::Available;
    }
}
