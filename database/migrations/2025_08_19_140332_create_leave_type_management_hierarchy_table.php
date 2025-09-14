<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('leave_type_management_hierarchy', function (Blueprint $table) {
            $table->id();
            $table->uuid('leave_type_id');
            $table->unsignedBigInteger('management_hierarchy_id');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');
            $table->foreign('management_hierarchy_id')->references('id')->on('management_hierarchies')->onDelete('cascade');

            // Unique constraint to prevent duplicate entries
            $table->unique(['leave_type_id', 'management_hierarchy_id'], 'leave_type_management_hierarchy_unique');

            // Indexes for performance
            $table->index('leave_type_id');
            $table->index('management_hierarchy_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_type_management_hierarchy');
    }
};
