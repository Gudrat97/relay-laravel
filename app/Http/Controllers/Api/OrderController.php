<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignCourierRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Courier;
use App\Models\Order;
use App\Services\OrderWorkflow;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly OrderWorkflow $workflow,
    ) {}

    /**
     * GET /api/orders?status=pending&zone_id=2&per_page=20
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'zone_id' => ['nullable', 'integer', 'exists:zones,id'],
            'courier_id' => ['nullable', 'integer', 'exists:couriers,id'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $orders = Order::query()
            ->with(['zone', 'courier'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['zone_id'] ?? null, fn ($q, $zone) => $q->where('zone_id', $zone))
            ->when($filters['courier_id'] ?? null, fn ($q, $courier) => $q->where('courier_id', $courier))
            ->latest()
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        return OrderResource::collection($orders);
    }

    /**
     * POST /api/orders
     * The price and promised time are calculated on the server, never sent by the client.
     */
    public function store(StoreOrderRequest $request): OrderResource
    {
        $data = $request->validated();
        $distance = (float) $data['distance_km'];

        $order = Order::create([
            ...$data,
            'price_cents' => $this->pricing->quoteCents($distance),
            'promised_at' => $this->pricing->promisedAt($distance),
        ]);

        return new OrderResource($order->load(['zone', 'courier']));
    }

    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load(['zone', 'courier']));
    }

    /**
     * POST /api/orders/{order}/assign  { "courier_id": 4 }
     */
    public function assign(AssignCourierRequest $request, Order $order): OrderResource
    {
        $courier = Courier::findOrFail($request->validated('courier_id'));

        return new OrderResource($this->workflow->assign($order, $courier));
    }

    /**
     * PATCH /api/orders/{order}/status  { "status": "picked_up" }
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): OrderResource
    {
        return new OrderResource($this->workflow->transition($order, $request->status()));
    }
}
