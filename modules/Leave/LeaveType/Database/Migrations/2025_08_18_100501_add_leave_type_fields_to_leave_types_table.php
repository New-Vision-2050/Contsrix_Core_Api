<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_08_18_100501_add_leave_type_fields_to_leave_types_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->boolean('is_payed')->default(false)->after('name');
            $table->boolean('is_deduct_from_balance')->default(false)->after('is_payed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn(['is_payed', 'is_deduct_from_balance']);
        });
    }
};
