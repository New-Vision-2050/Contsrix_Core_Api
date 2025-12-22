<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_09_08_112350_create_eco_discount_products_table Migration
{
    public function up()
    {
        Schema::create('eco_discount_products', function (Blueprint $table) {
            $table->uuid('eco_discount_id');
            $table->uuid('eco_product_id');
            $table->timestamps();

            $table->primary(['eco_discount_id', 'eco_product_id']);
            $table->foreign('eco_discount_id')->references('id')->on('eco_discounts')->onDelete('cascade');
            $table->foreign('eco_product_id')->references('id')->on('eco_products')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('eco_discount_products');
    }
};
