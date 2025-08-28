<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('client_details', function (Blueprint $table) {
            $table->tinyInteger("is_created_by_owner")->default(0);
        });
    }
};
