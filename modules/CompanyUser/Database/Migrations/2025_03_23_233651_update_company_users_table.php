<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->uuid("global_company_user_id")->nullable();
        });
    }
};
