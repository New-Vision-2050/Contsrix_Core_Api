<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('login_way_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string("login_option");//otp , password , barcode
            $table->integer('order');
            $table->json("drivers")->nullable();
            $table->json("login_option_alternatives")->nullable();
            $table->uuid( "login_way_id")->index();

            $table->timestamps();
        });
    }
};
