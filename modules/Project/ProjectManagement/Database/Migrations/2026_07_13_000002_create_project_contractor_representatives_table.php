<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_contractor_representatives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_contractor_id');
            $table->string('name');
            $table->string('mobile')->nullable();
            $table->string('nationality')->nullable();
            $table->timestamps();

            $table->foreign('project_contractor_id')
                ->references('id')
                ->on('contractors')
                ->cascadeOnDelete();

            $table->index('project_contractor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_contractor_representatives');
    }
};
