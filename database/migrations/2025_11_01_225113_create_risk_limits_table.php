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
        Schema::create('risk_limits', function (Blueprint $table) {
            $table->id();
            $table->decimal('daily_max_loss', 18, 4);
            $table->decimal('max_position_qty', 18, 4);
            $table->integer('max_orders_per_min');
            $table->enum('mode', ['paper', 'live'])->default('paper');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_limits');
    }
};
