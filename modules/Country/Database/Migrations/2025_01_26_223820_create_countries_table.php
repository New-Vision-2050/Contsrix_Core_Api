<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {

        Schema::create('countries', function (Blueprint $table) {
            $table->id('id')->primary();
            $table->string('name');
            $table->string('iso3');
            $table->string('numeric_code');
            $table->string('iso2');
            $table->string('phonecode');
            $table->string('capital');
            $table->string('currency');
            $table->string('currency_name');
            $table->string('currency_symbol');
            $table->string('tld')->nullable();
            $table->string('native')->nullable();
            $table->string('region')->nullable();
            $table->string('region_id')->nullable();
            $table->string('subregion')->nullable();
            $table->string('subregion_id')->nullable();
            $table->string('nationality');
            $table->text('timezones');
            $table->text('translations')->nullable();
            $table->string('latitude');
            $table->string('longitude');
            $table->string('emoji');
            $table->string('emojiU');
            $table->string('flag');
            $table->string('wikiDataId')->nullable();
            $table->uuid('sms_driver_id')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }
};
