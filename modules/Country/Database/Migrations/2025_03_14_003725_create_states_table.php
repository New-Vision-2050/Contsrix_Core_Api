<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
//        'country_id' => 70,
//                'country_code' => 'ET',
//                'fips_code' => '54',
//                'iso2' => 'SN',
//                'type' => 'region',
//                'level' => NULL,
//                'parent_id' => NULL,
//                'latitude' => '6.51569110',
//                'longitude' => '36.95410700',
//                'created_at' => '2019-10-06 00:48:35',
//                'updated_at' => '2024-12-17 00:17:36',
//                'flag' => 1,
//                'wikiDataId' => 'Q203193',
        Schema::create('states', function (Blueprint $table) {
            $table->id('id')->primary();
            $table->string('name');
            $table->foreignId("country_id")->index();
            $table->string('country_code');
            $table->string('fips_code')->nullable();
            $table->string('iso2')->nullable();
            $table->string('type');
            $table->string('level')->nullable();
            $table->string('parent_id')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('flag');
            $table->string('wikiDataId')->nullable();



            $table->timestamps();
        });
    }
};
