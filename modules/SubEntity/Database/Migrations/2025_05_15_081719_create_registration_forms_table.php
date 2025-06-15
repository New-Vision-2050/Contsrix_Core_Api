<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('registration_forms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->json('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }
};
