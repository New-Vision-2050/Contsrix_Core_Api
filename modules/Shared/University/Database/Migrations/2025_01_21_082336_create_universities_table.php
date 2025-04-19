<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('universities')) {
            Schema::create('universities', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('country_id');
                $table->string('link')->nullable();
                $table->timestamps();

                $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
            });
        }
    }

};
