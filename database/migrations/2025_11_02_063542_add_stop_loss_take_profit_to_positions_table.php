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
        Schema::table('positions', function (Blueprint $table) {
            $table->decimal('stop_loss', 10, 4)->nullable()->after('avg_entry_price');
            $table->decimal('take_profit', 10, 4)->nullable()->after('stop_loss');
            $table->decimal('trailing_stop', 10, 4)->nullable()->after('take_profit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn(['stop_loss', 'take_profit', 'trailing_stop']);
        });
    }
};
