<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('website_theme_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->boolean('is_default')->default(false);
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('website_theme_settings');
    }
};
