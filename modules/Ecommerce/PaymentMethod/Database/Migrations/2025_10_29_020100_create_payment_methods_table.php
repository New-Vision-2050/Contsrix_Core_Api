<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_10_29_020100_create_payment_methods_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 50); // cash, card, bank_transfer, digital_wallet, etc.
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
        Schema::dropIfExists('payment_methods');
    }
};
