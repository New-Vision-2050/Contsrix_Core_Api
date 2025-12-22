<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class 2025_10_28_002600_make_unit_nullable_in_eco_products extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('eco_products', function (Blueprint $table) {
            $table->string('unit')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eco_products', function (Blueprint $table) {
            // Revert unit field back to non-nullable with default
            $table->string('unit')->default('piece')->change();
        });
    }
};
