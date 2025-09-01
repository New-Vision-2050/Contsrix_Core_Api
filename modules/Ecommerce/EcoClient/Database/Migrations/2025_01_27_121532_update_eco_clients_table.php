<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('eco_clients', function (Blueprint $table) {
            $table->string('tag')->default('normal');
        });
    }
};
