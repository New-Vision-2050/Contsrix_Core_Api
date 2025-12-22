<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_26_140928_create_website_themes_table Migration
{
    public function up()
    {
        Schema::create('website_themes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('url')->nullable();
            $table->integer('radius')->nullable();
            $table->integer('html_font_size')->nullable();
            $table->text('font_family')->nullable();
            $table->string('font_size')->nullable();
            $table->string('font_weight_light')->nullable();
            $table->string('font_weight_regular')->nullable();
            $table->string('font_weight_medium')->nullable();
            $table->string('font_weight_bold')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('website_themes');
    }
};
