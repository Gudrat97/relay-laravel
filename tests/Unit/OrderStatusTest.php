<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Services\PricingService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    #[Test]
    public function an_order_moves_forward_one_step_at_a_time(): void
    {
        $this->assertTrue(OrderStatus::Pending->canTransitionTo(OrderStatus::Assigned));
        $this->assertTrue(OrderStatus::Assigned->canTransitionTo(OrderStatus::PickedUp));
        $this->assertTrue(OrderStatus::PickedUp->canTransitionTo(OrderStatus::Delivered));

        $this->assertFalse(OrderStatus::Pending->canTransitionTo(OrderStatus::Delivered));
        $this->assertFalse(OrderStatus::PickedUp->canTransitionTo(OrderStatus::Cancelled));
    }

    #[Test]
    public function finished_orders_cannot_change(): void
    {
        foreach ([OrderStatus::Delivered, OrderStatus::Cancelled] as $status) {
            $this->assertTrue($status->isFinal());
            $this->assertSame([], $status->allowedNext());
        }
    }

    #[Test]
    public function short_trips_pay_the_minimum_fare(): void
    {
        $pricing = new PricingService;

        $this->assertSame(PricingService::MINIMUM_CENTS, $pricing->quoteCents(0.5));
        $this->assertSame(250 + 85 * 10, $pricing->quoteCents(10));
    }
}
