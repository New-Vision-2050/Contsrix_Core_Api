<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('applied_attendance_constraints', function (Blueprint $table) {
            $table->dropForeign(['constraint_id']);
            $table->dropColumn('constraint_id');
        });
    }

    public function down()
    {
        Schema::table('applied_attendance_constraints', function (Blueprint $table) {
            $table->uuid('constraint_id');
            $table->foreign('constraint_id')->references('id')->on('attendance_constraints')->onDelete('cascade');
        });
    }
};
