<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sub_entities', function (Blueprint $table) {
            $table->string('origin_super_entity')->after('super_entity');
        });
    }
};
