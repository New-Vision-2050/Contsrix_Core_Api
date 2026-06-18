<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_task_end_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_task_request_id');
            $table->uuid('company_id');
            $table->uuid('procedure_setting_id')->nullable();
            $table->uuid('requested_by');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('pending'); // pending | approved | rejected
            $table->uuid('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->uuid('current_procedure_step_id')->nullable();
            $table->timestamps();

            $table->index('employee_task_request_id', 'eter_end_task_idx');
            $table->index('company_id', 'eter_end_company_idx');

            $table->foreign('employee_task_request_id')
                ->references('id')->on('employee_task_requests')
                ->onDelete('cascade');

            $table->foreign('requested_by', 'eter_end_requested_by_fk')
                ->references('id')->on('users');

            $table->foreign('procedure_setting_id', 'eter_end_procedure_setting_fk')
                ->references('id')->on('procedure_settings')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_task_end_requests');
    }
};
