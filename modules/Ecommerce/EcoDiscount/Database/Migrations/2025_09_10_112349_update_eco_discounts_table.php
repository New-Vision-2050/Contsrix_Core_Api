<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_09_10_112349_update_eco_discounts_table Migration
{
    public function up()
    {
        Schema::table('eco_discounts', function (Blueprint $table) {
            $table->string('type_discount')->nullable()->after('type');
        });
    }
};
