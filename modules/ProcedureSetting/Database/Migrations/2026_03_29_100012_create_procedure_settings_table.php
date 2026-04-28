<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            Schema::dropIfExists('procedure_setting_steps');
            Schema::dropIfExists('procedure_settings');

            Schema::create('procedure_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->enum('type', ProcedureSettingType::values());
                $table->enum('execute_type', ['parallel', 'sequence']);
                $table->string('icon')->nullable();
                $table->decimal('percentage', 5, 2)->default(0);
                $table->unsignedSmallInteger('deadline_days')->nullable();
                $table->unsignedSmallInteger('deadline_hours')->nullable();
                $table->uuid('escalation_user_id')->nullable()->index();

                $table->uuid('company_id')->nullable()->index();
                $table->timestamps();

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('cascade');
            });

            if (Schema::hasTable('users')) {
                Schema::table('procedure_settings', function (Blueprint $table) {
                    $table->foreign('escalation_user_id')
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                });
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            Schema::dropIfExists('procedure_setting_steps');
            Schema::dropIfExists('procedure_settings');
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
};
