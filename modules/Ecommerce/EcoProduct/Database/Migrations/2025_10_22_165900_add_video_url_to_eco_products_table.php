<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_10_22_165900_add_video_url_to_eco_products_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('eco_products', function (Blueprint $table) {
            $table->string('video_url', 500)->nullable()->after('other_photos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eco_products', function (Blueprint $table) {
            $table->dropColumn('video_url');
        });
    }
};
