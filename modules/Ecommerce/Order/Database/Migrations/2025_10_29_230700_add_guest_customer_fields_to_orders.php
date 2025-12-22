<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_10_29_230700_add_guest_customer_fields_to_orders Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_name', 100)->nullable()->after('customer_type');
            $table->string('customer_phone', 20)->nullable()->after('customer_name');
            $table->string('customer_email', 100)->nullable()->after('customer_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['customer_name', 'customer_phone', 'customer_email']);
        });
    }
};
