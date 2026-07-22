<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connection_phase_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_completion_phase_id')->constrained('connection_completion_phases')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connection_phase_statuses');
    }
};
