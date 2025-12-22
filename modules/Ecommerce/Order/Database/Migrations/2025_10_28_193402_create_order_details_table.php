<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_10_28_193402_create_order_details_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('order_id')->nullable();
            $table->uuid('product_id')->nullable();
            $table->string('digital_file_after_sell', 191)->nullable();
            $table->text('product_details')->nullable();
            $table->integer('qty')->default(0);
            $table->double('price')->default(0);
            $table->double('tax')->default(0);
            $table->double('discount')->default(0);
            $table->string('tax_model', 20)->default('exclude');
            $table->string('delivery_status', 15)->default('pending');
            $table->string('payment_status', 15)->default('unpaid');
            $table->timestamps();
            $table->uuid('shipping_method_id')->nullable();
            $table->string('variant', 255)->nullable();
            $table->string('variation', 255)->nullable();
            $table->string('discount_type', 30)->nullable();
            $table->tinyInteger('is_stock_decreased')->default(1);
            $table->integer('refund_request')->default(0);

            // Indexes
            $table->index(['order_id']);
            $table->index(['product_id']);
            $table->index(['delivery_status']);
            $table->index(['payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
