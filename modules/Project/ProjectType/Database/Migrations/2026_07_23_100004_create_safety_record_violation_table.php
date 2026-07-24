<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_record_violation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('safety_record_id');
            $table->uuid('violation_id');
            $table->decimal('weight', 5, 2)->nullable();
            $table->timestamps();

            $table->foreign('safety_record_id')->references('id')->on('safety_records')->cascadeOnDelete();
            $table->foreign('violation_id')->references('id')->on('violations')->cascadeOnDelete();
            $table->unique(['safety_record_id', 'violation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_record_violation');
    }
};
