<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_08_26_094736_update_comapnies_table Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->tinyInteger("is_client")->default(0);
        });
    }
};
