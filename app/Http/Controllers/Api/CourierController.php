<?php

namespace App\Http\Controllers\Api;

use App\Enums\CourierStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourierResource;
use App\Models\Courier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class CourierController extends Controller
{
    private const SORTS = [
        'name' => ['name', 'asc'],
        'rating' => ['rating', 'desc'],
        'deliveries' => ['deliveries_count', 'desc'],
    ];

    /**
     * GET /api/couriers?status=available&search=ann&sort=deliveries
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(CourierStatus::class)],
            'search' => ['nullable', 'string', 'max:60'],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTS))],
        ]);

        [$column, $direction] = self::SORTS[$filters['sort'] ?? 'deliveries'];

        $couriers = Courier::query()
            ->with('zone')
            ->withDeliveryStats()
            ->search($filters['search'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderBy($column, $direction)
            ->get();

        return CourierResource::collection($couriers);
    }

    public function show(Courier $courier): CourierResource
    {
        $courier->load('zone')->loadCount([
            'orders as deliveries_count' => fn ($q) => $q->where('status', 'delivered'),
            'orders as on_time_count' => fn ($q) => $q->where('status', 'delivered')->whereColumn('delivered_at', '<=', 'promised_at'),
        ]);

        return new CourierResource($courier);
    }
}
