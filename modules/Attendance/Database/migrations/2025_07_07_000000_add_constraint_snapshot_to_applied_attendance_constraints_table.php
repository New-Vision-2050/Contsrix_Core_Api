<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('applied_attendance_constraints', function (Blueprint $table) {
            $table->json('constraint_snapshot')->nullable()->after('constraint_id')
                ->comment('Snapshot of constraint configuration at the time of application');
        });
    }

    public function down()
    {
        Schema::table('applied_attendance_constraints', function (Blueprint $table) {
            $table->dropColumn('constraint_snapshot');
        });
    }
};
