<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_01_27_121532_update_eco_clients_table Migration
{
    public function up()
    {
        Schema::table('eco_clients', function (Blueprint $table) {
            $table->string('tag')->default('normal');
        });
    }
};
