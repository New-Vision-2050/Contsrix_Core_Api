<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_26_140941_create_website_color_palettes_table Migration
{
    public function up()
    {
        Schema::create('website_color_palettes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('website_theme_id');
            $table->string('name')->nullable();
            $table->string('primary')->nullable();
            $table->string('light')->nullable();
            $table->string('dark')->nullable();
            $table->string('contrast')->nullable();
            $table->timestamps();

            $table->foreign('website_theme_id')->references('id')->on('website_themes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('website_color_palettes');
    }
};
