<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * يعتمد على procedure_settings (ترحيل 2026_03_29_100012).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('procedure_setting_steps');

        Schema::create('procedure_setting_steps', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->uuid('employee_id')->nullable()->index();
            $table->boolean('is_accept')->default(false);
            $table->boolean('is_approve')->default(false);
            $table->integer('duration')->default(0)->comment('Duration in days');
            $table->enum('forms', ['approve', 'accept', 'financial'])->nullable();
            $table->uuid('procedure_setting_id')->index();
            $table->uuid('company_id')->nullable()->index();
            $table->timestamps();

            $table->foreign('procedure_setting_id')
                ->references('id')
                ->on('procedure_settings')
                ->cascadeOnDelete();

            $table->foreign('employee_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedure_setting_steps');
    }
};
