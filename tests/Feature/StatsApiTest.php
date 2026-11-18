<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatsApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_compares_the_period_with_the_one_before(): void
    {
        $this->travelTo(now()->setTime(18, 0));
        $zone = Zone::factory()->create(['name' => 'Centre']);

        // Three orders in the last 7 days: two on time, one late.
        Order::factory()->for($zone)->delivered(now()->subDays(1))->create(['price_cents' => 1000]);
        Order::factory()->for($zone)->delivered(now()->subDays(2))->create(['price_cents' => 1000]);
        Order::factory()->for($zone)->delivered(now()->subDays(3), minutesLate: 10)->create(['price_cents' => 1000]);

        // One order in the 7 days before that.
        Order::factory()->for($zone)->delivered(now()->subDays(9))->create(['price_cents' => 500]);

        // Not delivered, so it must not count.
        Order::factory()->for($zone)->create();

        $this->getJson('/api/stats?days=7')
            ->assertOk()
            ->assertJsonPath('data.totals.deliveries', 3)
            ->assertJsonPath('data.totals.revenue_cents', 3000)
            ->assertJsonPath('data.totals.on_time_rate', 66.7)
            ->assertJsonPath('data.previous.deliveries', 1)
            ->assertJsonPath('data.zones.0.zone', 'Centre')
            ->assertJsonCount(7, 'data.daily');
    }

    #[Test]
    public function it_only_accepts_known_ranges(): void
    {
        $this->getJson('/api/stats?days=12')->assertUnprocessable();
    }
}
