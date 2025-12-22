<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_10_28_193404_create_order_transactions_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('order_transactions');
        
        Schema::create('order_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('order_id');
            $table->decimal('order_amount', 50, 2)->default(0.00);
            $table->string('received_by', 191);
            $table->string('status', 191)->nullable();
            $table->decimal('delivery_charge', 50, 2)->default(0.00);
            $table->decimal('tax', 50, 2)->default(0.00);
            $table->timestamps();
            $table->uuid('customer_id')->nullable();
            $table->string('delivered_by', 191)->default('admin');
            $table->string('payment_method', 191)->nullable();
            $table->string('transaction_id', 191)->nullable();

            // Indexes
            $table->index(['order_id']);
            $table->index(['customer_id']);
            $table->index(['status']);
            $table->index(['payment_method']);
            $table->index(['transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_transactions');
    }
};
