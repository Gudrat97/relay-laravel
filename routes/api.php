<?php

use App\Http\Controllers\Api\CourierController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\StatsController;
use Illuminate\Support\Facades\Route;

// Read-only endpoints used by the dashboard.
Route::get('stats', StatsController::class)->name('api.stats');
Route::get('couriers', [CourierController::class, 'index'])->name('api.couriers.index');
Route::get('couriers/{courier}', [CourierController::class, 'show'])->name('api.couriers.show');
Route::get('orders', [OrderController::class, 'index'])->name('api.orders.index');
Route::get('orders/{order}', [OrderController::class, 'show'])->name('api.orders.show');

// Changes need a Sanctum token: Authorization: Bearer <token>
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('orders', [OrderController::class, 'store'])->name('api.orders.store');
    Route::post('orders/{order}/assign', [OrderController::class, 'assign'])->name('api.orders.assign');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('api.orders.status');
});
