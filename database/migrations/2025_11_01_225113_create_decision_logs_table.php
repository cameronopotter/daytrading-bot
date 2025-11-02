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
        Schema::create('decision_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_run_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('level', ['info', 'warn', 'error'])->default('info');
            $table->string('context');
            $table->text('message');
            $table->json('payload')->nullable();
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decision_logs');
    }
};
