<?php

namespace App\Services;

use App\Enums\CourierStatus;
use App\Enums\OrderStatus;
use App\Models\Courier;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moves orders through their life cycle and keeps courier statuses in step.
 */
final class OrderWorkflow
{
    public function assign(Order $order, Courier $courier): Order
    {
        if ($order->status !== OrderStatus::Pending) {
            throw ValidationException::withMessages([
                'order' => 'Only pending orders can be assigned to a courier.',
            ]);
        }

        if (! $courier->status->canTakeOrders()) {
            throw ValidationException::withMessages([
                'courier_id' => "{$courier->name} is {$courier->status->value} and cannot take a new order.",
            ]);
        }

        return DB::transaction(function () use ($order, $courier) {
            $order->update([
                'courier_id' => $courier->id,
                'status' => OrderStatus::Assigned,
            ]);

            $courier->update(['status' => CourierStatus::Delivering]);

            return $order->refresh()->load('courier', 'zone');
        });
    }

    public function transition(Order $order, OrderStatus $next): Order
    {
        if (! $order->status->canTransitionTo($next)) {
            throw ValidationException::withMessages([
                'status' => "An order that is {$order->status->label()} cannot be changed to {$next->label()}.",
            ]);
        }

        $order->loadMissing('courier');

        return DB::transaction(function () use ($order, $next) {
            $attributes = ['status' => $next];

            if ($next === OrderStatus::Delivered) {
                $now = now();
                $attributes['delivered_at'] = $now;
                $attributes['delivery_minutes'] = (int) abs($order->created_at->diffInMinutes($now));
            }

            $order->update($attributes);

            // A courier is free again once the order is finished.
            if ($next->isFinal() && $order->courier) {
                $order->courier->update(['status' => CourierStatus::Available]);
            }

            return $order->refresh()->load('courier', 'zone');
        });
    }
}
