<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_08_18_100600_create_public_holidays_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('public_holidays', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->unsignedBigInteger('country_id');
            $table->date('date');
            $table->timestamps();

            $table->index(['country_id', 'date']);
            $table->index('name');

            // Note: Foreign key constraint for country_id can be added later when countries table structure is confirmed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_holidays');
    }
};
