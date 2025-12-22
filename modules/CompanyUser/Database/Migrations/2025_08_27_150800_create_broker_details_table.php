<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_08_27_150800_create_broker_details_table Migration
{
    public function up()
    {
        Schema::create('broker_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('type');
            $table->string("company_representative_name")->nullable();
            $table->string("registration_number")->nullable();
            $table->string("company_name")->nullable();
            $table->uuid("user_id");
            $table->uuid("company_id")->nullable();
            
            $table->foreign("company_id")->references("id")->on("companies");
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('broker_details');
    }
};
