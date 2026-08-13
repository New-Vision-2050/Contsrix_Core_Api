<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_weekly_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('serial_number', 30)->unique();
            $table->uuid('company_id')->nullable();
            $table->uuid('project_id');
            $table->uuid('created_by')->nullable();
            $table->string('name');
            $table->date('from_date');
            $table->date('to_date');
            $table->string('status', 20)->default('pending'); // pending|ready|failed
            $table->string('file_path', 500)->nullable(); // Spatie media uuid when file_disk=media
            $table->string('file_disk', 50)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
            $table->index(['company_id', 'project_id']);
            $table->index(['from_date', 'to_date']);

            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_weekly_reports');
    }
};
