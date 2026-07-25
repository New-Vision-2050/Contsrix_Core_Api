<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->nullable();
            $table->uuid('project_id')->nullable();
            // string morph id supports both UUID parents (ProjectNotification)
            // and bigint parents (ProjectOrderPermit)
            $table->string('morphable_type');
            $table->string('morphable_id');
            $table->string('order_type')->nullable();
            $table->date('date')->nullable();
            $table->time('time')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('required_score', 5, 2)->nullable();
            $table->decimal('earned_score', 5, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('consultant_engineer')->nullable();
            $table->string('consultant')->nullable();
            $table->uuid('contractor_id')->nullable();
            $table->uuid('assigned_user_id')->nullable();
            $table->timestamps();

            $table->index(['morphable_type', 'morphable_id']);
            $table->index(['project_id', 'status']);
            $table->index('assigned_user_id');

            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->foreign('contractor_id')->references('id')->on('project_contractors')->nullOnDelete();
            $table->foreign('assigned_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_records');
    }
};
