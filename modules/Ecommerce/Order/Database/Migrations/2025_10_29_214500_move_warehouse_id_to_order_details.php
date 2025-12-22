<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_10_29_214500_move_warehouse_id_to_order_details Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add warehouse_id to order_details table
        Schema::table('order_details', function (Blueprint $table) {
            $table->uuid('warehouse_id')->nullable()->after('product_id');
            $table->index(['warehouse_id']);
        });

        // Remove foreign key constraint and warehouse_id from orders table
        Schema::table('orders', function (Blueprint $table) {
            // Drop foreign key constraint first if it exists
            $table->dropForeign(['warehouse_id']);
            // Drop index if it exists
            $table->dropIndex(['warehouse_id']);
            // Now drop the column
            $table->dropColumn('warehouse_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add warehouse_id back to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('warehouse_id')->nullable()->after('company_id');
            $table->index(['warehouse_id']);
        });

        // Remove warehouse_id from order_details table
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropIndex(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });
    }
};
