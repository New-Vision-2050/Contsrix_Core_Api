<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
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
