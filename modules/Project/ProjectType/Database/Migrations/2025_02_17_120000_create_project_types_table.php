<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->unsignedBigInteger('reference_project_type_id')->nullable()->index()->comment('Reference to second level project type for schema inheritance');
            $table->uuid('company_id')->nullable()->index();
            $table->string('path', 500)->nullable()->index();
            $table->boolean('is_created')->default(true)->comment('false for seeded data, true for user-created');
            $table->boolean('is_have_schema')->default(false)->comment('true if this project type has a specific schema');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')
                ->references('id')
                ->on('project_types')
                ->onDelete('cascade');

            $table->foreign('reference_project_type_id')
                ->references('id')
                ->on('project_types')
                ->onDelete('set null');

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_types');
    }
};
