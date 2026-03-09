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
        Schema::create('members', function (Blueprint $table) {
            $table->id('member_id');
            $table->string('account_no')->unique();
            $table->string('meter_no')->nullable();
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('lname');
            $table->string('suffix')->nullable();
            $table->string('barangay')->nullable();
            $table->string('municipality')->nullable();
            $table->string('purok')->nullable();
            $table->integer('connection_status')->default(1); // 1=connected, 0=disconnected
            $table->decimal('prev_balance', 10, 2)->default(0);
            $table->boolean('is_read')->default(false);
            $table->boolean('is_paid')->default(false);
            $table->date('reconnection_date')->nullable();
            $table->timestamps();
            
            // Add indexes
            $table->index(['connection_status']);
            $table->index(['account_no']);
            $table->index(['fname', 'lname']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
