<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_phase_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_completion_phase_id')->constrained('project_completion_phases')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_phase_statuses');
    }
};
