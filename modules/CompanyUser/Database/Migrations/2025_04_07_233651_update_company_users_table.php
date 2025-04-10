<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->string("nickname")->nullable();
            $table->integer("is_default")->default(0);
            $table->string("birthdate_gregorian")->nullable();
            $table->string("birthdate_hijri")->nullable();
            $table->string("nationality")->nullable();
            $table->string("gender")->nullable();
        });
    }
};
