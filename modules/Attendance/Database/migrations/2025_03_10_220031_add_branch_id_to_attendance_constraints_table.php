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
        Schema::table('attendance_constraints', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('department_id');
            $table->boolean('inherit_from_parent')->default(false)->after('is_active');

            // Add index for branch-based queries
            $table->index(['company_id', 'branch_id', 'is_active']);
            $table->index(['branch_id', 'constraint_type']);

            // Foreign key constraint to management_hierarchies table
            $table->foreign('branch_id')->references('id')->on('management_hierarchies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_constraints', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['company_id', 'branch_id', 'is_active']);
            $table->dropIndex(['branch_id', 'constraint_type']);
            $table->dropColumn(['branch_id', 'inherit_from_parent']);
        });
    }
};
