<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StatsController extends Controller
{
    /**
     * GET /api/stats?days=30
     */
    public function __invoke(Request $request, StatsService $stats): JsonResponse
    {
        $days = (int) ($request->validate([
            'days' => ['nullable', 'integer', 'in:7,30,90'],
        ])['days'] ?? 30);

        // The figures change slowly, so a short cache keeps the dashboard fast.
        $data = Cache::remember(
            "stats:{$days}:".now()->format('Y-m-d-H-i'),
            now()->addMinute(),
            fn () => $stats->forPeriod($days)
        );

        return response()->json(['data' => $data]);
    }
}
