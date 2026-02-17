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
        Schema::create('project_type_schemas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_type_id');
            $table->unsignedBigInteger('schema_id');
            $table->timestamps();

            $table->foreign('project_type_id')
                ->references('id')
                ->on('project_types')
                ->onDelete('cascade');

            $table->foreign('schema_id')
                ->references('id')
                ->on('project_schemas')
                ->onDelete('cascade');

            $table->unique(['project_type_id', 'schema_id']);
            $table->index('project_type_id');
            $table->index('schema_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_type_schemas');
    }
};
