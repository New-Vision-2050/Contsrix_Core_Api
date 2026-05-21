<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_task_approval_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_task_request_id');
            $table->uuid('company_id');
            $table->uuid('requested_by');
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('pending'); // pending | approved | rejected
            $table->uuid('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->uuid('current_procedure_step_id')->nullable();
            $table->timestamps();

            $table->index('employee_task_request_id', 'etar_task_idx');
            $table->index('company_id', 'etar_company_idx');

            $table->foreign('employee_task_request_id')
                ->references('id')->on('employee_task_requests')
                ->onDelete('cascade');

            $table->foreign('requested_by')
                ->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_task_approval_requests');
    }
};
