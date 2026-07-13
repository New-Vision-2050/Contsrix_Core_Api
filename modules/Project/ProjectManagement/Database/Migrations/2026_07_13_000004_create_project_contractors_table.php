<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project_contractors')) {
            Schema::create('project_contractors', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('company_id')->nullable();
                $table->uuid('project_id')->nullable();
                $table->string('name');
                $table->string('number')->nullable();
                $table->string('mobile')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('tax_card')->nullable();
                $table->string('commercial_register')->nullable();
                $table->string('activity')->nullable();
                $table->string('email')->nullable();
                $table->unsignedBigInteger('country_id')->nullable(); // يفترض أن countries.id من نوع bigint
                $table->string('logo')->nullable();
                $table->string('project_contractor_id')->nullable();
                $table->string('project_manager_name')->nullable();
                $table->string('project_manager_phone')->nullable();
                $table->string('project_manager_nationality')->nullable();
                $table->string('project_manager_email')->nullable();
                $table->timestamps();

                $table->foreign('project_id')
                    ->references('id')
                    ->on('projects')
                    ->cascadeOnDelete();
                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->cascadeOnDelete();

                $table->unique(['project_id', 'project_contractor_id'], 'project_contractors_reference_unique');
            });
        } else {
            Schema::table('project_contractors', function (Blueprint $table) {
                $columns = [
                    'tax_card', 'commercial_register', 'activity', 'email',
                    'country_id', 'logo', 'project_contractor_id',
                    'project_manager_name', 'project_manager_phone',
                    'project_manager_nationality', 'project_manager_email'
                ];

                foreach ($columns as $col) {
                    if (!Schema::hasColumn('project_contractors', $col)) {
                        match ($col) {
                            'country_id' => $table->unsignedBigInteger('country_id')->nullable(),
                            'logo'       => $table->string('logo')->nullable(),
                            default      => $table->string($col)->nullable(),
                        };
                    }
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_contractors');
    }
};
