<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->string("whatsapp")->nullable();
            $table->string("facebook")->nullable();
            $table->string("telegram")->nullable();
            $table->string("instagram")->nullable();
            $table->string("snapchat")->nullable();
            $table->string("linkedin")->nullable();
        });
    }
};

