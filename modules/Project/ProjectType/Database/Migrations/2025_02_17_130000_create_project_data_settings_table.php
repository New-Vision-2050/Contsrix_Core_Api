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
        Schema::create('project_data_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_type_id')->unique();
            $table->boolean('is_reference_number')->default(1);
            $table->boolean('is_name_project')->default(1);
            $table->boolean('is_client')->default(1);
            $table->boolean('is_responsible_engineer')->default(1);
            $table->boolean('is_number_contract')->default(1);
            $table->boolean('is_central_cost')->default(1);
            $table->boolean('is_project_value')->default(1);
            $table->boolean('is_start_date')->default(1);
            $table->boolean('is_achievement_percentage')->default(1);
            $table->timestamps();

            $table->foreign('project_type_id')
                ->references('id')
                ->on('project_types')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_data_settings');
    }
};
