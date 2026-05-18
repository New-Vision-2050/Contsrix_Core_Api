<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_task_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_task_request_id');
            $table->uuid('company_id');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->string('source', 30)->default('manual');
            $table->decimal('start_latitude', 10, 7)->nullable();
            $table->decimal('start_longitude', 10, 7)->nullable();
            $table->decimal('end_latitude', 10, 7)->nullable();
            $table->decimal('end_longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('employee_task_request_id', 'ets_task_idx');
            $table->index('company_id', 'ets_company_idx');

            $table->foreign('employee_task_request_id')
                ->references('id')
                ->on('employee_task_requests')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_task_sessions');
    }
};
