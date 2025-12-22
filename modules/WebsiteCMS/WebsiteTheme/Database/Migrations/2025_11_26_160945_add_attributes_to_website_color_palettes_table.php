<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_26_160945_add_attributes_to_website_color_palettes_table Migration
{
    public function up()
    {
        Schema::table('website_color_palettes', function (Blueprint $table) {

            $table->string("secondary")->nullable();
        });
    }

    public function down()
    {
        Schema::table('website_color_palettes', function (Blueprint $table) {
            $table->dropColumn('secondary');
        });
    }
};
