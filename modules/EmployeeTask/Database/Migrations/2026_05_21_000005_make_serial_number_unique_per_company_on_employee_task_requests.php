<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The serial_number column had a global unique index, but serial numbers are
 * only required to be unique per company. With CustomBelongsToTenant scoping
 * countForYear() to the current company, every company independently starts
 * at TASK-YYYY-00001 — colliding on the global unique constraint.
 *
 * Fix: drop the global unique index and replace it with a composite unique
 * index on (company_id, serial_number).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_task_requests', function (Blueprint $table) {
            $table->dropUnique('employee_task_requests_serial_number_unique');
            $table->unique(['company_id', 'serial_number'], 'etr_company_serial_unique');
        });
    }

    public function down(): void
    {
        Schema::table('employee_task_requests', function (Blueprint $table) {
            $table->dropUnique('etr_company_serial_unique');
            $table->unique('serial_number');
        });
    }
};
