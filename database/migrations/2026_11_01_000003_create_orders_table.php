<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('customer_name');
            $table->string('customer_phone', 32);
            $table->string('pickup_address');
            $table->string('dropoff_address');
            $table->foreignId('zone_id')->constrained()->restrictOnDelete();
            $table->foreignId('courier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16)->default('pending');
            $table->decimal('distance_km', 5, 1);
            $table->unsignedInteger('price_cents');
            $table->timestamp('promised_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedSmallInteger('delivery_minutes')->nullable();
            $table->timestamps();

            // The dashboard filters delivered orders by date on every request.
            $table->index(['status', 'delivered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
