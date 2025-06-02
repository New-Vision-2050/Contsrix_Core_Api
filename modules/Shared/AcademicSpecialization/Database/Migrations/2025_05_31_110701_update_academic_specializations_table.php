<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('academic_specializations', function (Blueprint $table) {
            $table->uuid('academic_qualification_id')->nullable();
        });
    }
};
