<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {

        Schema::create('cities', function (Blueprint $table) {
            $table->id('id')->primary();
            $table->string('name');
            $table->foreignId("state_id")->index();
            $table->string('state_code');
            $table->foreignId("country_id")->index();
            $table->string('country_code');
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('flag');
            $table->string('wikiDataId')->nullable();

            $table->string("created_at")->nullable();
            $table->string("updated_at")->nullable();
        });
    }
};
