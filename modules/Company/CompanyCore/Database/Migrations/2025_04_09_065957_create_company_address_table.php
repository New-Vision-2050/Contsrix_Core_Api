<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('company_address', function (Blueprint $table) {

            $table->uuid('id')->primary();
            $table->uuid("company_id")->index();
            $table->uuid("country_id")->index();
            $table->uuid("city_id")->index();
            $table->uuid("state_id")->index();
            $table->string("neighborhood_name");
            $table->string("street_name");
            $table->string("building_number");
            $table->string("additional_phone");
            $table->string("postal_code");
            $table->timestamps();
        });
    }
};
