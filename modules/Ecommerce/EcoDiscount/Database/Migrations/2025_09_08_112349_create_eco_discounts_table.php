<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_09_08_112349_create_eco_discounts_table Migration
{
    public function up()
    {
        Schema::create('eco_discounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('code')->unique();
            $table->enum('type', ['percentage', 'fixed_amount', 'buy_x_get_y'])->default('percentage');
            $table->decimal('value', 10, 2); // percentage or fixed amount
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->decimal('max_discount_amount', 10, 2)->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->datetime('start_date')->nullable();
            $table->datetime('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('applies_to', ['all_products', 'specific_products', 'categories'])->default('all_products');
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'start_date', 'end_date']);
            $table->index('code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('eco_discounts');
    }
};
