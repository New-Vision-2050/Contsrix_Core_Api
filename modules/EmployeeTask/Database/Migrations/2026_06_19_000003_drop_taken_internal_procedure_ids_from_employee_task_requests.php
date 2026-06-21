<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_task_requests', function (Blueprint $table) {
            if (Schema::hasColumn('employee_task_requests', 'taken_internal_procedure_ids')) {
                $table->dropColumn('taken_internal_procedure_ids');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_task_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_task_requests', 'taken_internal_procedure_ids')) {
                $table->json('taken_internal_procedure_ids')->nullable()->after('location_confirmed_at');
            }
        });
    }
};
