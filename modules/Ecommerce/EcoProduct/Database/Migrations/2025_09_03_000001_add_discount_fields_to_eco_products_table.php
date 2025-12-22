<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_09_03_000001_add_discount_fields_to_eco_products_table Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('eco_products', function (Blueprint $table) {
            $table->boolean('has_discount')->default(0)->after('is_visible');
            $table->decimal('discount_amount', 10, 2)->nullable()->after('has_discount');
            $table->decimal('discount_percentage', 5, 2)->nullable()->after('discount_amount');
            $table->decimal('max_discount_amount', 10, 2)->nullable()->after('discount_percentage');
            $table->timestamp('discount_start_date')->nullable()->after('discount_percentage');
            $table->timestamp('discount_end_date')->nullable()->after('discount_start_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('eco_products', function (Blueprint $table) {
            $table->dropColumn([
                'has_discount',
                'discount_amount',
                'discount_percentage',
                'max_discount_amount',
                'discount_start_date',
                'discount_end_date',
            ]);
        });
    }
};
