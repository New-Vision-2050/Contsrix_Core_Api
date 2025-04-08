<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->unique(false)->change();
            $table->string('phone')->unique(false)->change();
            $table->uuid("global_company_user_id");
            $table->uuid("company_id")->nullable();

        });
    }
};
