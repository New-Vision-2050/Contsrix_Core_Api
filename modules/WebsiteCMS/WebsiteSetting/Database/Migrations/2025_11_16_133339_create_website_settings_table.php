<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('website_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('main_color')->nullable();
            $table->string('second_color')->nullable();
            $table->string('background_color')->nullable();
            $table->string('logo')->nullable();
            $table->string('website_address')->nullable();
            $table->uuid('company_id')->nullable();
            $table->timestamps();

            // Check if companies table exists before adding foreign key
            if (Schema::hasTable('companies')) {
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('website_settings');
    }
};
