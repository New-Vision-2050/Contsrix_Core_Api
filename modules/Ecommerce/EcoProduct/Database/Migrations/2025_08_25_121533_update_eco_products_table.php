<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_08_25_121533_update_eco_products_table Migration
{
    public function up()
    {
        Schema::table('eco_products', function (Blueprint $table) {
            $table->string('type')->nullable();
            $table->uuid('category_id')->nullable()->index();
            $table->uuid('sub_category_id')->nullable()->index();
            $table->uuid('brand_id')->nullable()->index();
        });
    }


};
