<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_26_160943_add_slug_to_website_color_palettes_table Migration
{
    public function up()
    {
        Schema::table('website_color_palettes', function (Blueprint $table) {
            $table->string('slug')->after('name')->nullable();
            $table->index(['website_theme_id', 'slug']);
        });
    }

    public function down()
    {
        Schema::table('website_color_palettes', function (Blueprint $table) {
            $table->dropIndex(['website_theme_id', 'slug']);
            $table->dropColumn('slug');
        });
    }
};
