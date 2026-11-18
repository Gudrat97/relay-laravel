<?php

namespace App\Services;

use App\Models\Order;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Builds the figures for the dashboard: totals for a period, the same totals
 * for the period before it, revenue per day and deliveries per zone.
 */
final class StatsService
{
    /**
     * @return array<string, mixed>
     */
    public function forPeriod(int $days, ?CarbonImmutable $now = null): array
    {
        $to = ($now ?? CarbonImmutable::now())->endOfDay();
        $from = $to->subDays($days - 1)->startOfDay();
        $previousTo = $from->subSecond();
        $previousFrom = $from->subDays($days);

        return [
            'period' => [
                'days' => $days,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'totals' => $this->totals($from, $to),
            'previous' => $this->totals($previousFrom, $previousTo),
            'daily' => $this->daily($from, $to),
            'zones' => $this->byZone($from, $to),
        ];
    }

    /**
     * @return array{deliveries: int, revenue_cents: int, on_time_rate: float|null, avg_delivery_minutes: float|null}
     */
    private function totals(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $row = Order::query()
            ->deliveredBetween($from, $to)
            ->selectRaw('COUNT(*) as deliveries')
            ->selectRaw('COALESCE(SUM(price_cents), 0) as revenue_cents')
            ->selectRaw('SUM(CASE WHEN delivered_at <= promised_at THEN 1 ELSE 0 END) as on_time')
            ->selectRaw('AVG(delivery_minutes) as avg_minutes')
            ->toBase()
            ->first();

        $deliveries = (int) $row->deliveries;

        return [
            'deliveries' => $deliveries,
            'revenue_cents' => (int) $row->revenue_cents,
            'on_time_rate' => $deliveries ? round($row->on_time / $deliveries * 100, 1) : null,
            'avg_delivery_minutes' => $row->avg_minutes !== null ? round((float) $row->avg_minutes, 1) : null,
        ];
    }

    /**
     * One row per day, including days without deliveries.
     *
     * @return list<array{date: string, deliveries: int, revenue_cents: int}>
     */
    private function daily(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = Order::query()
            ->deliveredBetween($from, $to)
            ->selectRaw('DATE(delivered_at) as day, COUNT(*) as deliveries, SUM(price_cents) as revenue_cents')
            ->groupBy(DB::raw('DATE(delivered_at)'))
            ->toBase()
            ->get()
            ->keyBy('day');

        return collect(CarbonPeriod::create($from, '1 day', $to->startOfDay()))
            ->map(function ($date) use ($rows) {
                $row = $rows->get($date->toDateString());

                return [
                    'date' => $date->toDateString(),
                    'deliveries' => (int) ($row->deliveries ?? 0),
                    'revenue_cents' => (int) ($row->revenue_cents ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{zone: string, deliveries: int}>
     */
    private function byZone(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return Order::query()
            ->deliveredBetween($from, $to)
            ->join('zones', 'zones.id', '=', 'orders.zone_id')
            ->groupBy('zones.id', 'zones.name')
            ->orderByDesc('deliveries')
            ->selectRaw('zones.name as zone, COUNT(*) as deliveries')
            ->toBase()
            ->get()
            ->map(fn ($row) => ['zone' => $row->zone, 'deliveries' => (int) $row->deliveries])
            ->all();
    }
}
