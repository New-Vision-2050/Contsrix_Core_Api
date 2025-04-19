<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->string("other_phone")->nullable();
            $table->string("address")->nullable();
            $table->string("address_attendance")->nullable();


        });
    }
};
