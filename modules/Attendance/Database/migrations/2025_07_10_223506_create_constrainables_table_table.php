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
        Schema::create('constrainables', function (Blueprint $table) {
            $table->uuid('attendance_constraint_id');

            $table->uuid('constrainable_id');
            $table->string('constrainable_type');

            $table->boolean('is_default')->default(false);

            $table->primary(['attendance_constraint_id', 'constrainable_id', 'constrainable_type'], 'constrainables_primary_key');

            $table->foreign('attendance_constraint_id', 'fk_constrainable_constraint')
                  ->references('id')
                  ->on('attendance_constraints')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('constrainables');
    }
};
