<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->renameColumn('Work_permit_start_date', 'work_permit_start_date');
            $table->renameColumn('Work_permit_end_date', 'work_permit_end_date');
            $table->renameColumn('Work_permit', 'work_permit');
        });
    }

    public function down(): void
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->renameColumn('work_permit_start_date', 'Work_permit_start_date');
            $table->renameColumn('work_permit_end_date', 'Work_permit_end_date');
            $table->renameColumn('work_permit', 'Work_permit');
        });
    }
};
