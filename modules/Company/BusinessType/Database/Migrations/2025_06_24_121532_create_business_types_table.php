<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('business_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->nullable();
            $table->timestamps();
        });
    }
};
