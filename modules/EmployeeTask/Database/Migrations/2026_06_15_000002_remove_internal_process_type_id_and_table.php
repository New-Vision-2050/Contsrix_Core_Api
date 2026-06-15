<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('employee_task_requests', 'internal_process_type_id')) {
            try {
                DB::statement('ALTER TABLE employee_task_requests DROP FOREIGN KEY etr_internal_process_type_fk');
            } catch (\Throwable) {
                // Foreign key may not exist
            }

            Schema::table('employee_task_requests', function (Blueprint $table) {
                $table->dropColumn('internal_process_type_id');
            });
        }

        Schema::dropIfExists('internal_process_types');
    }

    public function down(): void
    {
        // Re-create internal_process_types (bare minimum)
        if (! Schema::hasTable('internal_process_types')) {
            Schema::create('internal_process_types', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('company_id');
                $table->string('entity_type', 50);
                $table->string('name', 255);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('employee_task_requests', 'internal_process_type_id')) {
            Schema::table('employee_task_requests', function (Blueprint $table) {
                $table->uuid('internal_process_type_id')->nullable()->after('project_id');
                $table->foreign('internal_process_type_id', 'etr_internal_process_type_fk')
                    ->references('id')
                    ->on('internal_process_types')
                    ->nullOnDelete();
            });
        }
    }
};
