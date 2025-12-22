<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_08_28_080436_update_companies_table Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->tinyInteger("is_broker")->default(0);
        });
    }
};
