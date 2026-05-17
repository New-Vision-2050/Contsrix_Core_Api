<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_constraint_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('attendance_constraint_id');
            $table->uuid('company_id');
            $table->string('name')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('radius')->default(100);
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('attendance_constraint_id')
                ->references('id')
                ->on('attendance_constraints')
                ->onDelete('cascade');

            $table->index(['attendance_constraint_id'], 'acl_constraint_id_index');
            $table->index(['company_id'], 'acl_company_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_constraint_locations');
    }
};
