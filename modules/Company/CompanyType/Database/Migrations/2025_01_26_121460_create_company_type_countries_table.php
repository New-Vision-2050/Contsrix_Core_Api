<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('company_type_countries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('company_type_id')->index();
            $table->string('country_id')->index();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }
};
