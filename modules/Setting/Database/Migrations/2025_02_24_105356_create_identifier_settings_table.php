<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('identifier_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string("key");
            $table->tinyInteger("status")->default(0);
            $table->timestamps();
        });
    }
};
