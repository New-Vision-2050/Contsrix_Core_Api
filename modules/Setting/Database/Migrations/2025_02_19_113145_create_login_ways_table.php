<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('login_ways', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string("name");
            $table->tinyInteger("default")->default(0);
            $table->uuid("company_id")->index();
            $table->timestamps();
        });
    }
};
