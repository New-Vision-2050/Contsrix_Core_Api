<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('website_color_palettes', function (Blueprint $table) {
            $table->json('attributes')->nullable();
        });
    }

    public function down()
    {
        Schema::table('website_color_palettes', function (Blueprint $table) {
            $table->dropColumn('attributes');
        });
    }
};
