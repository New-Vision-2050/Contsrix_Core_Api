<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('professional_bodies', function (Blueprint $table) {
            $table->string('country_id')->index();
        });
    }
};
