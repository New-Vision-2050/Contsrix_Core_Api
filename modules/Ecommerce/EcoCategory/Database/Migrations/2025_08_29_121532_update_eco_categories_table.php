<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_08_29_121532_update_eco_categories_table Migration
{
    public function up()
    {
        Schema::table('eco_categories', function (Blueprint $table) {
            $table->integer('priority')->default(0);
        });
    }
};
