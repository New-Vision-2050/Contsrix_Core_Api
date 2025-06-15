<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employment_contracts', function (Blueprint $table) {
            $table->renameColumn('nature_work', 'nature_work_id');

            $table->renameColumn('type_working_hours', 'type_working_hour_id');

            $table->renameColumn('right_terminate', 'right_terminate_id');
        });
    }

    public function down(): void
    {
        Schema::table('employment_contracts', function (Blueprint $table) {
            $table->renameColumn('nature_work_id', 'nature_work');

            $table->renameColumn('type_working_hour_id', 'type_working_hours');

            $table->renameColumn('right_terminate_id', 'right_terminate');
        });
    }
};
