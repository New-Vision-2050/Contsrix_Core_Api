<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('applied_attendance_constraints', function (Blueprint $table) {
            $table->uuid('company_id')->after('id');
        });
    }


};
