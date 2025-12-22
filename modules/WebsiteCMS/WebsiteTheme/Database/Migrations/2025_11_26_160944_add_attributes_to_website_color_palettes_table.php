<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_26_160944_add_attributes_to_website_color_palettes_table Migration
{
    public function up()
    {
        Schema::table('website_color_palettes', function (Blueprint $table) {
            $table->json('attributes')->nullable();
            $table->string("divider")->nullable();
            $table->string("disabled")->nullable();
            $table->string("paper")->nullable();
            $table->string("default")->nullable();
            $table->string("black")->nullable();
            $table->string("white")->nullable();
        });
    }

    public function down()
    {
        Schema::table('website_color_palettes', function (Blueprint $table) {
            $table->dropColumn('attributes');
            $table->dropColumn('divider');
            $table->dropColumn('disabled');
            $table->dropColumn('paper');
            $table->dropColumn('default');
            $table->dropColumn('black');
            $table->dropColumn('white');
        });
    }
};
