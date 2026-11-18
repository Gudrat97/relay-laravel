<?php

namespace Tests\Feature;

use App\Enums\CourierStatus;
use App\Enums\OrderStatus;
use App\Models\Courier;
use App\Models\Order;
use App\Models\User;
use App\Models\Zone;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }

    #[Test]
    public function guests_cannot_create_orders(): void
    {
        $this->postJson('/api/orders', [])->assertUnauthorized();
    }

    #[Test]
    public function it_creates_an_order_with_a_server_side_price(): void
    {
        $this->signIn();
        $zone = Zone::factory()->create();

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Aylin Demir',
            'customer_phone' => '+1 555 010 2030',
            'pickup_address' => '14 Mill Lane',
            'dropoff_address' => '3 River Street',
            'zone_id' => $zone->id,
            'distance_km' => 5,
            'price_cents' => 1, // must be ignored
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.price.cents', (new PricingService)->quoteCents(5))
            ->assertJsonPath('data.zone', $zone->name);

        $this->assertDatabaseCount('orders', 1);
    }

    #[Test]
    public function it_validates_the_order(): void
    {
        $this->signIn();

        $this->postJson('/api/orders', [
            'customer_phone' => 'abc',
            'pickup_address' => 'Same place',
            'dropoff_address' => 'Same place',
            'distance_km' => 0,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_name', 'customer_phone', 'dropoff_address', 'zone_id', 'distance_km']);
    }

    #[Test]
    public function it_assigns_an_available_courier(): void
    {
        $this->signIn();
        $order = Order::factory()->create();
        $courier = Courier::factory()->status(CourierStatus::Available)->create();

        $this->postJson("/api/orders/{$order->id}/assign", ['courier_id' => $courier->id])
            ->assertOk()
            ->assertJsonPath('data.status', 'assigned')
            ->assertJsonPath('data.courier.id', $courier->id);

        $this->assertSame(CourierStatus::Delivering, $courier->fresh()->status);
    }

    #[Test]
    public function it_refuses_a_courier_who_is_busy(): void
    {
        $this->signIn();
        $order = Order::factory()->create();
        $courier = Courier::factory()->status(CourierStatus::Delivering)->create();

        $this->postJson("/api/orders/{$order->id}/assign", ['courier_id' => $courier->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('courier_id');

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    #[Test]
    public function it_blocks_status_changes_that_skip_steps(): void
    {
        $this->signIn();
        $order = Order::factory()->create();

        $this->patchJson("/api/orders/{$order->id}/status", ['status' => 'delivered'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    #[Test]
    public function delivering_an_order_frees_the_courier(): void
    {
        $this->signIn();
        $courier = Courier::factory()->status(CourierStatus::Delivering)->create();
        $order = Order::factory()->create([
            'courier_id' => $courier->id,
            'status' => OrderStatus::PickedUp,
            'created_at' => now()->subMinutes(30),
        ]);

        $this->patchJson("/api/orders/{$order->id}/status", ['status' => 'delivered'])
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered');

        $order->refresh();
        $this->assertNotNull($order->delivered_at);
        $this->assertSame(30, $order->delivery_minutes);
        $this->assertSame(CourierStatus::Available, $courier->fresh()->status);
    }
}
