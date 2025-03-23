<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // This migration will run in each tenant's schema
        
        // Example: Create a tenant-specific projects table
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('pending');
            $table->string('company_user_id')->index(); // Reference to the user who created the project
            $table->timestamps();
        });

        // Example: Create a tenant-specific tasks table
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('project_id')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->default('pending');
            $table->string('assigned_to')->nullable()->index(); // Reference to the company_user_id
            $table->timestamps();
        });

        // Example: Create a tenant-specific documents table
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('file_path');
            $table->string('file_type');
            $table->integer('file_size');
            $table->string('uploaded_by')->index(); // Reference to the company_user_id
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('projects');
    }
};