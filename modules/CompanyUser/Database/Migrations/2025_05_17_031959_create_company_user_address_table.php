<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('company_user_address', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid("country_id")->nullable()->index();
            $table->uuid("city_id")->index()->nullable();
            $table->uuid("state_id")->index()->nullable();
            $table->string("neighborhood_name")->nullable();
            $table->string("street_name")->nullable();
            $table->string("building_number")->nullable();
            $table->string("additional_phone")->nullable();
            $table->string("postal_code")->nullable();
            $table->uuid("global_company_user_id")->index();
            $table->string("latitude")->nullable();
            $table->string("longitude")->nullable();
            $table->timestamps();
        });
    }
};
