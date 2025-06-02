<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('client_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('type');
            $table->uuid("broker_id")->nullable();
            $table->string("company_representative_name")->nullable();
            $table->string("registration_number")->nullable();
            $table->string("company_name")->nullable();

            $table->uuid("user_id");

            $table->timestamps();
        });
    }
};
