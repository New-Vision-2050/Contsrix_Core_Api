<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {    Schema::dropIfExists('process_steps');
        Schema::dropIfExists('processes');
        if (!Schema::hasTable('processes')) {
            Schema::create('processes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('processable_id');
                $table->string('processable_type');
                $table->unsignedInteger('sort_order')->nullable();
                $table->string('execute_type');
                $table->string('status'); // ProcessStatus Enum
                $table->json('template_snapshot')->nullable();
                $table->timestamps();

                // Indexes
                $table->index(['processable_id', 'processable_type']);
                $table->unique(['processable_id', 'processable_type', 'sort_order'], 'processes_morph_type_sort_unique');
            });
        }

        if (!Schema::hasTable('process_steps')) {
            Schema::create('process_steps', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('process_id');
                $table->unsignedBigInteger('step_id')->nullable();
                $table->unsignedInteger('template_step_order')->nullable();
                $table->uuid('assigned_user_id');
                $table->unsignedBigInteger('escalation_management_hierarchy_id')->nullable();
                $table->string('status');
                $table->uuid('action_by')->nullable();
                $table->timestamp('acted_at')->nullable();
                $table->timestamps();

                // Foreign Keys
                $table->foreign('process_id')
                    ->references('id')->on('processes')
                    ->cascadeOnDelete();

                $table->foreign('step_id')
                    ->references('id')->on('procedure_setting_steps')
                    ->nullOnDelete();

                $table->foreign('assigned_user_id')
                    ->references('id')->on('users')
                    ->restrictOnDelete();

                $table->foreign('escalation_management_hierarchy_id', 'process_steps_hierarchy_foreign')
                    ->references('id')->on('management_hierarchies')
                    ->nullOnDelete();

                $table->foreign('action_by')
                    ->references('id')->on('users')
                    ->nullOnDelete();

                // Indexes
                $table->index(['process_id', 'status']);
                $table->index('template_step_order');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_steps');
        Schema::dropIfExists('processes');
    }
};
