<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_master_lists', function (Blueprint $table) {
            // Add connection_status column if it doesn't exist
            if (!Schema::hasColumn('monthly_master_lists', 'connection_status')) {
                $table->integer('connection_status')->default(1)->after('billing_date'); // 1 = connected, 0 = disconnected
            }
        });
    }

    public function down(): void
    {
        Schema::table('monthly_master_lists', function (Blueprint $table) {
            if (Schema::hasColumn('monthly_master_lists', 'connection_status')) {
                $table->dropColumn('connection_status');
            }
        });
    }
};
