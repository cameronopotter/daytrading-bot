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
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('symbol');
            $table->decimal('qty', 18, 4);
            $table->decimal('avg_entry_price', 18, 4);
            $table->decimal('unrealized_pl', 18, 4)->default(0);
            $table->enum('mode', ['paper', 'live'])->default('paper');
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->unique(['symbol', 'mode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
