<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_09_21_002600_create_eco_app_settings_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('eco_app_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');

            // Theme & UI Settings
            $table->string('background_color', 7)->default('#1e1b4b'); // خلفية التطبيق
            $table->boolean('enable_search')->default(true);

            //first page settings
            $table->boolean('show_logo_on_first_page')->default(true);

            //Front page settings
            $table->boolean('show_logo_on_front_page')->default(true);
            $table->integer('count_photos')->default(1);

            //Display products
            $table->string('product_display_category')->default('latest'); // Product category filter (news, best_seller, latest)
            $table->string('product_display_type')->default('list'); // Display type (List, Grid)
            $table->integer('product_columns_count')->default(2); // Number of columns (1, 2, 3, 4)
            $table->integer('product_rows_count')->default(8); // Number of products (4, 8, 16, 32)
            $table->boolean('show_products_in_app')->default(true); // Show products in application

            //Display favorites
            $table->boolean('show_favorites_search')->default(false); // Show search in favorites
            $table->boolean('show_favorites_delete')->default(false); // Show delete in favorites
            $table->boolean('show_favorites_products')->default(true); // Show favorites products
            $table->string('favorites_display_type')->default('list'); // Favorites display type (List, Grid)
            $table->boolean('show_favorites_in_app')->default(true); // Show favorites in application

            //Product selection settings
            $table->boolean('show_product_image')->default(true); // Show product image
            $table->boolean('show_product_rating')->default(true); // Show product rating
            $table->boolean('show_similar_products')->default(true); // Show similar products
            $table->boolean('show_product_price')->default(true); // Show product price
            $table->boolean('show_product_shipping')->default(true); // Show shipping
            $table->boolean('show_product_description')->default(true); // Show description
            $table->boolean('show_product_color_code')->default(true); // Product color code
            $table->boolean('show_product_size')->default(true); // Small size
            $table->boolean('show_product_comment')->default(true); // Show product details
            $table->boolean('can_product_comment')->default(true); // Show product reviews

            //cart settings
            $table->boolean('show_cart_products')->default(false); // Show cart products
            $table->string('cart_display_type')->default('list'); // Cart display type (List, Grid)
            $table->integer('cart_columns_count')->default(2); // Number of columns (1, 2, 3)
            $table->boolean('show_cart_in_app')->default(true); // Show cart in application

            //Product card settings
            $table->boolean('show_product_name')->default(false); // Show product name
            $table->boolean('show_product_description_card')->default(true); // Show product description
            $table->boolean('show_product_price_card')->default(true); // Show product price
            $table->boolean('show_product_color')->default(true); // Show product color
            $table->boolean('show_product_size_card')->default(true); // Show product size
            $table->boolean('show_similar_products_card')->default(true); // Show similar products
            $table->string('product_card_display_type')->default('list'); // Product card display type (List, Grid)
            $table->integer('product_card_columns_count')->default(2); // Number of columns (1, 2, 3)
            $table->boolean('show_discount_code')->default(true); // Show discount code
            $table->boolean('show_payment_details')->default(true); // Show payment details
            $table->boolean('show_product_card_in_app')->default(true); // Show product card in application


            //filter settings
            $table->boolean('show_filter_in_app')->default(true); // Show filter system in application
            $table->boolean('show_category_filter')->default(true); // Show category filters in application
            $table->boolean('show_product_filter')->default(true); // Show product attribute filters
            $table->boolean('show_color_filter')->default(true); // Show color attribute filters
            $table->boolean('show_brand_filter')->default(true); // Show brand attribute filters
            $table->boolean('show_size_filter')->default(true); // Show size attribute filters
            $table->boolean('show_price_filter')->default(true); // Show price attribute filters
            $table->boolean('show_rating_filter')->default(true); // Show rating attribute filters
            $table->boolean('show_discount_filter')->default(true); // Show discount attribute filters

            //Terms and Conditions settings
            $table->boolean('show_terms_text')->default(true); // Show terms text
            $table->boolean('show_privacy_policy')->default(true); // Show privacy policy
            $table->boolean('show_return_policy')->default(true); // Show return policy

            
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            // Indexes
            $table->index('company_id');
            $table->unique(['company_id']); // One app setting per company
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eco_app_settings');
    }
};

