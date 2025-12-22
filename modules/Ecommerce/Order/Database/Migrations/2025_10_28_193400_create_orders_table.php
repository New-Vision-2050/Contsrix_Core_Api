<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_10_28_193400_create_orders_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('customer_id')->nullable();
            $table->tinyInteger('is_guest')->default(0);
            $table->string('customer_type', 10)->nullable();
            $table->string('payment_status', 15)->default('unpaid');
            $table->string('order_status', 50)->default('pending');
            $table->string('payment_method', 100)->nullable();
            $table->string('transaction_ref', 30)->nullable();
            $table->string('payment_by', 191)->nullable();
            $table->text('payment_note')->nullable();
            $table->double('order_amount')->default(0);
            $table->decimal('paid_amount', 18, 12)->default(0.000000000000);
            $table->decimal('bring_change_amount', 18, 12)->nullable()->default(0.000000000000);
            $table->string('bring_change_amount_currency', 255)->nullable();
            $table->string('is_pause', 20)->default('0');
            $table->string('cause', 191)->nullable();
            $table->text('shipping_address')->nullable();
            $table->timestamps();
            $table->double('discount_amount')->default(0);
            $table->string('discount_type', 30)->nullable();
            $table->string('coupon_code', 191)->nullable();
            $table->string('coupon_discount_bearer', 191)->default('inhouse');
            $table->string('shipping_responsibility', 255)->nullable();
            $table->uuid('shipping_method_id')->nullable();
            $table->decimal('shipping_cost', 8, 2)->default(0.00);
            $table->tinyInteger('is_shipping_free')->default(0);
            $table->string('order_group_id', 191)->default('def-order-group');
            $table->string('verification_code', 191)->default('0');
            $table->tinyInteger('verification_status')->default(0);
            $table->text('shipping_address_data')->nullable();
            $table->uuid('delivery_man_id')->nullable();
            $table->double('deliveryman_charge')->default(0);
            $table->date('expected_delivery_date')->nullable();
            $table->text('order_note')->nullable();
            $table->uuid('billing_address')->nullable();
            $table->text('billing_address_data')->nullable();
            $table->string('order_type', 191)->default('default_type');
            $table->decimal('extra_discount', 8, 2)->default(0.00);
            $table->string('extra_discount_type', 191)->nullable();
            $table->decimal('refer_and_earn_discount', 10, 2)->default(0.00);
            $table->string('free_delivery_bearer', 255)->nullable();
            $table->tinyInteger('checked')->default(0);
            $table->string('shipping_type', 191)->nullable();
            $table->string('delivery_type', 191)->nullable();
            $table->string('delivery_service_name', 191)->nullable();
            $table->string('third_party_delivery_tracking_id', 191)->nullable();

            // Indexes
            $table->index(['customer_id']);
            $table->index(['order_status']);
            $table->index(['payment_status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};