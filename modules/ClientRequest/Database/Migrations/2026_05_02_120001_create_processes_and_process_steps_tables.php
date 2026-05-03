<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_request_id');
            $table->string('type')->default('client_request');
            $table->string('execute_type');
            $table->string('status');
            $table->json('template_snapshot')->nullable();
            $table->timestamps();

            $table->unique(['client_request_id', 'type'], 'processes_client_request_type_unique');

            $table->foreign('client_request_id')
                ->references('id')
                ->on('client_requests')
                ->cascadeOnDelete();
        });

        Schema::create('process_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('process_id');
            $table->unsignedBigInteger('step_id')->nullable();
            $table->unsignedInteger('template_step_order')->nullable()->index();
            $table->uuid('assigned_user_id');
            $table->uuid('escalation_user_id')->nullable();
            $table->string('status');
            $table->uuid('action_by')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->foreign('process_id')
                ->references('id')
                ->on('processes')
                ->cascadeOnDelete();

            $table->index(['process_id', 'status'], 'process_steps_process_id_status_idx');

            if (Schema::hasTable('procedure_setting_steps')) {
                $table->foreign('step_id')
                    ->references('id')
                    ->on('procedure_setting_steps')
                    ->nullOnDelete();
            }

            if (Schema::hasTable('users')) {
                $table->foreign('assigned_user_id')
                    ->references('id')
                    ->on('users')
                    ->restrictOnDelete();

                $table->foreign('escalation_user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                $table->foreign('action_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_steps');
        Schema::dropIfExists('processes');
    }
};
