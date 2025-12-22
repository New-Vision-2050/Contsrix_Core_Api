<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_10_29_020600_create_payment_method_data_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_method_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 50)->unique(); // cash, card, bank_transfer, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_method_data');
    }
};
