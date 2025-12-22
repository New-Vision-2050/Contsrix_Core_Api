<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_09_22_160825_update_folders_table Migration
{
    public function up()
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->string("company_id");
        });
    }
};
