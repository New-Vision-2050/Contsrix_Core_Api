<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eco_order_details', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('eco_order_id')->nullable();
            $table->uuid('eco_product_id')->nullable();
            $table->uuid('shipping_method_id')->nullable();
            $table->uuid('warehouse_id')->nullable();
            
            $table->string('digital_file_after_sell', 191)->nullable();
            $table->text('product_details')->nullable();

            $table->integer('qty')->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);

            $table->string('tax_model', 20)->default('exclude');
            $table->string('delivery_status', 15)->default('pending');
            $table->string('payment_status', 15)->default('unpaid');

            $table->string('variant', 255)->nullable();
            $table->string('variation', 255)->nullable();
            $table->string('discount_type', 30)->nullable();

            $table->boolean('is_stock_decreased')->default(1);
            $table->integer('refund_request')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eco_order_details');
    }
};
