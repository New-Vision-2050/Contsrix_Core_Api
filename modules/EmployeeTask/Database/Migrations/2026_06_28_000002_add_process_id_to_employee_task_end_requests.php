<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_task_end_requests', function (Blueprint $table): void {
            $table->uuid('process_id')->nullable()->after('procedure_setting_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('employee_task_end_requests', function (Blueprint $table): void {
            $table->dropColumn('process_id');
        });
    }
};
