<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_task_extension_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_task_request_id');
            $table->uuid('company_id');
            $table->uuid('requested_by');
            $table->decimal('additional_hours', 6, 2);
            $table->text('reason')->nullable();
            $table->string('status', 30)->default('pending');
            $table->uuid('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index('employee_task_request_id', 'eter_task_idx');

            $table->foreign('employee_task_request_id', 'eter_task_request_fk')
                ->references('id')
                ->on('employee_task_requests')
                ->onDelete('cascade');

            $table->foreign('requested_by', 'eter_requested_by_fk')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_task_extension_requests');
    }
};
