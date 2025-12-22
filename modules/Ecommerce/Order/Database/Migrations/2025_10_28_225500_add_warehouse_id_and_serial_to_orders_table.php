<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_10_28_225500_add_warehouse_id_and_serial_to_orders_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add warehouse_id column
            $table->uuid('warehouse_id')->nullable()->after('company_id');
            $table->index('warehouse_id');
            
            // Add serial order number with auto increment
            $table->string('order_serial', 50)->nullable()->after('id');
            $table->unsignedBigInteger('order_number')->nullable()->after('order_serial');
            $table->index('order_serial');
            $table->index('order_number');
            
            // Add foreign key constraint for warehouse
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['warehouse_id']);
            
            // Drop indexes
            $table->dropIndex(['warehouse_id']);
            $table->dropIndex(['order_serial']);
            $table->dropIndex(['order_number']);
            
            // Drop columns
            $table->dropColumn(['warehouse_id', 'order_serial', 'order_number']);
        });
    }
};
