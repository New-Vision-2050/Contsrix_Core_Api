<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('super_entities_config', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('super_entity')->unique();
            $table->json('config');

            $table->timestamps();
        });
    }
};
