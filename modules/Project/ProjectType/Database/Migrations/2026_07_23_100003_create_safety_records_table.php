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
            $table->uuid('project_id')->nullable();
            $table->uuidMorphs('morphable');
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
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->foreign('contractor_id')->references('id')->on('project_contractors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_records');
    }
};
