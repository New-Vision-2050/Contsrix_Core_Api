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
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Required foreign keys (project_types uses integer IDs)
            $table->unsignedBigInteger('project_type_id');
            $table->unsignedBigInteger('sub_project_type_id');
            $table->unsignedBigInteger('sub_sub_project_type_id');
            
            // Nullable fields
            $table->string('name')->nullable();
            $table->uuid('responsible_employee_id')->nullable();
            $table->uuid('client_id')->nullable();
            $table->uuid('project_classification_id')->nullable();
            $table->uuid('cost_center_branch_id')->nullable();
            $table->uuid('management_id')->nullable();
            $table->uuid('currency_id')->nullable();
            $table->decimal('project_value', 15, 2)->nullable();
            
            // Standard fields
            $table->uuid('company_id');
            $table->integer('status')->default(1);
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('project_type_id')
                ->references('id')
                ->on('project_types')
                ->onDelete('restrict');
                
            $table->foreign('sub_project_type_id')
                ->references('id')
                ->on('project_types')
                ->onDelete('restrict');
                
            $table->foreign('sub_sub_project_type_id')
                ->references('id')
                ->on('project_types')
                ->onDelete('restrict');
                
            // Only add foreign keys for tables that definitely exist
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
                
            // Optional foreign keys - uncomment when tables exist
            // $table->foreign('responsible_employee_id')
            //     ->references('id')
            //     ->on('users')
            //     ->onDelete('set null');
            
            // $table->foreign('client_id')
            //     ->references('id')
            //     ->on('clients')
            //     ->onDelete('set null');
            
            // $table->foreign('cost_center_branch_id')
            //     ->references('id')
            //     ->on('branches')
            //     ->onDelete('set null');
            
            // $table->foreign('management_id')
            //     ->references('id')
            //     ->on('management_hierarchies')
            //     ->onDelete('set null');
            
            // $table->foreign('currency_id')
            //     ->references('id')
            //     ->on('currencies')
            //     ->onDelete('set null');
                
            // Indexes
            $table->index('project_type_id');
            $table->index('sub_project_type_id');
            $table->index('sub_sub_project_type_id');
            $table->index('responsible_employee_id');
            $table->index('client_id');
            $table->index('company_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
