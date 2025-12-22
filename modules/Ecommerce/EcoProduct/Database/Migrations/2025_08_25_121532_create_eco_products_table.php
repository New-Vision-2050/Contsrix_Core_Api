<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_08_25_121532_create_eco_products_table Migration
{
    public function up()
    {
        Schema::create('eco_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index(); // Assuming 'companies' table exists and has uuid 'id'

            $table->decimal('price', 10, 2);
            $table->string('sku')->unique(); // product code (SKU)

            $table->unsignedBigInteger('stock')->nullable();
            $table->uuid('warehouse_id')->index(); // Assuming 'warehouses' table exists and has uuid 'id'

            $table->tinyInteger('requires_shipping')->default(0); // requires shipping
            $table->tinyInteger('unlimited_quantity')->default(0); // unlimited stockcompany_id

            $table->tinyInteger('is_taxable')->default(1); // is product taxable?
            $table->tinyInteger('price_includes_vat')->default(0); // price includes VAT?
            $table->decimal('vat_percentage', 5, 2)->nullable(); // VAT percentage

            // Visibility
            $table->tinyInteger('is_visible')->default(1); // show product in store?

            $table->timestamps();
        });

        // Separate table for multiple taxes per product
        Schema::create('product_taxes', function (Blueprint $table) {
           $table->uuid('id')->primary();
           $table->uuid('company_id')->index();

            $table->foreignUuid('product_id')->constrained('eco_products')->onDelete('cascade');
            $table->uuid('country_id')->nullable(); // country for tax (assuming 'countries' table has uuid 'id')
            $table->string('tax_number')->nullable(); // tax registration number
            $table->decimal('tax_percentage', 5, 2)->nullable(); // tax percentage
            $table->tinyInteger('is_active')->default(1); // enable/disable this tax
            $table->timestamps();
        });

        Schema::create('product_details', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('product_id')->constrained('eco_products')->onDelete('cascade');
            $table->string('label');   // e.g. "Color"
            $table->string('value');   // e.g. "Black"
            $table->timestamps();
        });

        Schema::create('product_custom_fields', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('product_id')->constrained('eco_products')->onDelete('cascade');
            $table->string('field_name');  // e.g. "Warranty Period"
            $table->string('field_value'); // e.g. "2 years"
            $table->timestamps();
        });

        Schema::create('product_seo', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('product_id')->constrained('eco_products')->onDelete('cascade');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Drop tables in reverse order to avoid foreign key constraint issues
        Schema::dropIfExists('product_seo');
        Schema::dropIfExists('product_custom_fields');
        Schema::dropIfExists('product_details');
        Schema::dropIfExists('product_taxes');
        Schema::dropIfExists('eco_products'); // Corrected: Should drop 'eco_products'
    }
};
