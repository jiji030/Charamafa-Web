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
        Schema::table('monthly_master_lists', function (Blueprint $table) {
            $table->decimal('prev_CUM_consumption', 10, 2)->nullable()->default(0)->after('cum_consumption');
            $table->decimal('present_CUM_consumption', 10, 2)->nullable()->default(0)->after('prev_CUM_consumption');
            $table->string('prev_meter_reading')->nullable()->after('present_CUM_consumption');
            $table->string('present_meter_reading')->nullable()->after('prev_meter_reading');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_master_lists', function (Blueprint $table) {
            $table->dropColumn(['prev_CUM_consumption', 'present_CUM_consumption', 'prev_meter_reading', 'present_meter_reading']);
        });
    }
};
