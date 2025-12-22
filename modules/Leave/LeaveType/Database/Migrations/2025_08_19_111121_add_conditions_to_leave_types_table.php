<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_08_19_111121_add_conditions_to_leave_types_table Migration
{
    public function up()
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->text('conditions')->nullable()->after('is_deduct_from_balance');
        });
    }

    public function down()
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn('conditions');
        });
    }
};
