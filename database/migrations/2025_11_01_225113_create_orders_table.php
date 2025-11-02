<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_run_id')->nullable()->constrained()->onDelete('cascade');
            $table->uuid('client_order_id')->unique();
            $table->string('broker')->default('alpaca');
            $table->string('symbol');
            $table->enum('side', ['buy', 'sell']);
            $table->enum('type', ['market', 'limit', 'stop', 'stop_limit']);
            $table->decimal('qty', 18, 4);
            $table->decimal('limit_price', 18, 4)->nullable();
            $table->decimal('stop_price', 18, 4)->nullable();
            $table->string('time_in_force');
            $table->enum('status', ['new', 'partially_filled', 'filled', 'canceled', 'rejected'])->default('new');
            $table->string('broker_order_id')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->decimal('filled_qty', 18, 4)->default(0);
            $table->decimal('avg_fill_price', 18, 4)->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
