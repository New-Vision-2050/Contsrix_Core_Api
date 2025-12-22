<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_10_21_020300_recreate_eco_products_with_new_structure Migration{
    public function up(): void
    {
        // Disable foreign key checks temporarily
        Schema::disableForeignKeyConstraints();
        
        // Drop existing tables in correct order
        Schema::dropIfExists('product_seo');
        Schema::dropIfExists('product_custom_fields');
        Schema::dropIfExists('product_details');
        Schema::dropIfExists('product_taxes');
        Schema::dropIfExists('product_product');
        Schema::dropIfExists('eco_products');
        
        // Re-enable foreign key checks
        Schema::enableForeignKeyConstraints();

        // Create new eco_products table with updated structure
        Schema::create('eco_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            // Categories and Brand
            $table->uuid('category_id')->index();
            $table->uuid('sub_category_id')->nullable()->index();
            $table->uuid('sub_sub_category_id')->nullable()->index();
            $table->uuid('brand_id')->nullable()->index();
            
            // Countries will be handled via pivot table (product_countries)
            
            // Product type and unit
            $table->enum('type', ['digital', 'normal'])->default('normal');
            $table->string('unit')->default('piece'); // kg, m, liter, gram, piece, etc.
            
            // SKU and Warehouse
            $table->string('sku')->unique();
            $table->uuid('warehouse_id')->index();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            
            // Gender targeting
            $table->enum('gender', ['male', 'female', 'all'])->default('all');
            
            // Pricing
            $table->decimal('price', 10, 2);
            $table->integer('min_order_quantity')->default(1);
            $table->integer('stock')->nullable();
            
            // Discount system
            $table->enum('discount_type', ['amount', 'percentage'])->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
            
            // Tax configuration
            $table->decimal('vat_percentage', 5, 2)->nullable();
            $table->tinyInteger('price_includes_vat')->default(0);
            
            // Shipping configuration
            $table->decimal('shipping_amount', 10, 2)->nullable();
            $table->tinyInteger('shipping_included_in_price')->default(0);
            
            // Visibility
            $table->tinyInteger('is_visible')->default(1);
            
            // Media (stored as JSON arrays)
            $table->json('main_photo')->nullable(); // Main photo data
            $table->json('other_photos')->nullable(); // Array of other photos
            
            // SEO fields (stored in main table)
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('eco_categories')->onDelete('cascade');
            $table->foreign('sub_category_id')->references('id')->on('eco_categories')->onDelete('set null');
            $table->foreign('sub_sub_category_id')->references('id')->on('eco_categories')->onDelete('set null');
            $table->foreign('brand_id')->references('id')->on('eco_brands')->onDelete('set null');
        });

        // Create product_countries pivot table for many-to-many relationship
        Schema::create('product_countries', function (Blueprint $table) {
            $table->uuid('product_id');
            $table->uuid('country_id');
            
            $table->foreign('product_id')->references('id')->on('eco_products')->onDelete('cascade');
            // Remove foreign key to countries table since it may not exist or have different structure
            // $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
            
            $table->primary(['product_id', 'country_id']);
            $table->timestamps();
        });

        // Create product associations (related products)
        Schema::create('product_product', function (Blueprint $table) {
            $table->uuid('product_id');
            $table->uuid('related_product_id');
            
            $table->foreign('product_id')->references('id')->on('eco_products')->onDelete('cascade');
            $table->foreign('related_product_id')->references('id')->on('eco_products')->onDelete('cascade');
            
            $table->primary(['product_id', 'related_product_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Disable foreign key checks temporarily
        Schema::disableForeignKeyConstraints();
        
        // Drop tables in reverse order
        Schema::dropIfExists('product_product');
        Schema::dropIfExists('product_countries');
        Schema::dropIfExists('eco_products');
        
        // Re-enable foreign key checks
        Schema::enableForeignKeyConstraints();
    }
};
