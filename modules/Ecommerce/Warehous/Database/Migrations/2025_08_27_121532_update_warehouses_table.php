<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_08_27_121532_update_warehouses_table Migration
{
    public function up()
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->tinyInteger('is_active')->default(1)->nullable();
        });
    }
};
