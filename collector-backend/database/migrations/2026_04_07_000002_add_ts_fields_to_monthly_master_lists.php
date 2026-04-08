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
            $table->integer('ts_id')->nullable()->after('member_id');
            $table->string('ts_no')->nullable()->after('ts_id');
            $table->string('landmark')->nullable()->after('ts_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_master_lists', function (Blueprint $table) {
            $table->dropColumn(['ts_id', 'ts_no', 'landmark']);
        });
    }
};
