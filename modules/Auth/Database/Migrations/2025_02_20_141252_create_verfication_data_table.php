<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('verfication_data', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('token')->unique()->index();
            $table->uuid('user_id')->index();
            $table->json('data');
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();

        });
    }
};
