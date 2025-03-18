<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table("folders", function (Blueprint $table) {
            $table->string('modified')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('document_no')->nullable();
            $table->string('latest_activity')->nullable();
            $table->boolean('status')->nullable();
        });
    }
};
