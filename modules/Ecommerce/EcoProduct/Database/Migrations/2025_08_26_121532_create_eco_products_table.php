<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_08_26_121532_create_eco_products_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_product', function (Blueprint $table) {
            // Using UUIDs for both foreign keys
            $table->uuid('product_id');
            $table->uuid('related_product_id'); // Renamed to avoid confusion with the main product_id column

            // Define foreign key constraints
            $table->foreign('product_id')->references('id')->on('eco_products')->onDelete('cascade');

            $table->foreign('related_product_id')->references('id')->on('eco_products')->onDelete('cascade');

            // Make the pair unique
            $table->primary(['product_id', 'related_product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_product');
    }
};
