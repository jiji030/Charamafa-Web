<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_master_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_period_id')->constrained('billing_periods')->cascadeOnDelete();
            $table->unsignedBigInteger('member_id');

            $table->string('account_no');
            $table->string('meter_no')->nullable();
            $table->string('name');
            $table->integer('cum_consumption')->default(0);

            $table->decimal('minimum_amount', 10, 2)->default(0);
            $table->decimal('excess_cum', 10, 2)->default(0);
            $table->decimal('damage_charges', 10, 2)->default(0);
            $table->decimal('miscellaneous', 10, 2)->default(0);
            $table->decimal('aselco', 10, 2)->default(0);
            $table->decimal('diesel', 10, 2)->default(0);
            $table->decimal('others', 10, 2)->default(0);
            $table->decimal('total_bill', 10, 2)->default(0);            $table->date('billing_date')->nullable();
            $table->integer('connection_status')->default(1); // 1 = connected, 0 = disconnected

            $table->timestamps();

            $table->index(['billing_period_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_master_lists');
    }
};
