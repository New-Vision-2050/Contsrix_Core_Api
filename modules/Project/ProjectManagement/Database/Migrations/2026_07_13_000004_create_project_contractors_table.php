<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
            $table->uuid('country_id')->nullable();               // تغيير إلى uuid
            $table->string('logo')->nullable();
            $table->string('project_contractor_id')->nullable();
            $table->string('project_manager_name')->nullable();
            $table->string('project_manager_phone')->nullable();
            $table->string('project_manager_nationality')->nullable();
            $table->string('project_manager_email')->nullable();
            $table->timestamps();

            $table->foreign('project_id')
                ->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('company_id')
                ->references('id')->on('companies')->cascadeOnDelete();


            $table->unique(['project_id', 'project_contractor_id'], 'project_contractors_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_contractors');
    }
};
