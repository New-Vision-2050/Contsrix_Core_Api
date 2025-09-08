<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('eco_categories', function (Blueprint $table) {
            $table->tinyInteger('is_active')->default(1);
        });
    }
};
