<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('created_by')->nullable();
            $table->uuid('template_id')->nullable();

            // Auto-generated display name (locale-aware via HasTranslations) – derived
            // from selected report types + period when not provided by caller.
            $table->json('name');

            // Selected report types (string array, e.g. ["attendance_absence","leaves",...])
            $table->json('report_types');

            // Step1 — period & output formatting (denormalised for filtering / list view).
            $table->string('period_type', 20);            // monthly|weekly|quarterly|yearly
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month')->nullable();
            $table->unsignedTinyInteger('week')->nullable();
            $table->unsignedTinyInteger('quarter')->nullable();
            $table->date('period_start')->nullable();     // resolved start of period (branch TZ)
            $table->date('period_end')->nullable();       // resolved end of period (branch TZ)

            $table->string('export_format', 10);          // pdf|excel|csv
            $table->string('language', 5);                // ar|en
            $table->string('paper_size', 10);             // A4|Letter|A3
            $table->string('print_orientation', 15);      // portrait|landscape

            // Full wizard payload (Step1..Step5) snapshot — JSON.
            $table->json('config');

            // Generation state machine.
            $table->string('status', 20)->default('pending'); // pending|processing|ready|failed
            $table->string('file_path', 500)->nullable();
            $table->string('file_disk', 50)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index('company_id');
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'created_at']);
            $table->index(['company_id', 'period_type', 'year', 'month']);
            $table->index('template_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
