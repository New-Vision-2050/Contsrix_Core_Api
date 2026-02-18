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
        Schema::create('attachment_contract_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_type_id')->unique();
            $table->boolean('is_name')->default(1);
            $table->boolean('is_type')->default(1);
            $table->boolean('is_size')->default(1);
            $table->boolean('is_creator')->default(1);
            $table->boolean('is_create_date')->default(1);
            $table->boolean('is_downloadable')->default(1);
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
        Schema::dropIfExists('attachment_contract_settings');
    }
};
