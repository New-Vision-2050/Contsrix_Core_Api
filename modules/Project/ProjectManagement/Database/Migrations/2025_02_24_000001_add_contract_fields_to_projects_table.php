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
        Schema::table('projects', function (Blueprint $table) {
            // Rename responsible_employee_id to manager_id
            $table->renameColumn('responsible_employee_id', 'manager_id');

            // Add branch_id (related to management_hierarchies - uses integer ID)
            $table->unsignedBigInteger('branch_id')->nullable()->after('manager_id');

            // Add project owner polymorphic fields
            $table->string('project_owner_type')->nullable()->after('branch_id');
            $table->uuid('project_owner_id')->nullable()->after('project_owner_type');

            // Add contract_id
            $table->uuid('contract_id')->nullable()->after('project_owner_id');

            // Add indexes for new fields
            $table->index('branch_id');
            $table->index('project_owner_type');
            $table->index('project_owner_id');
            $table->index('contract_id');
            $table->index(['project_owner_type', 'project_owner_id']);
        });

        // Add foreign key constraints after column creation
        Schema::table('projects', function (Blueprint $table) {
            $table->foreign('branch_id')
                ->references('id')
                ->on('management_hierarchies')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['branch_id']);

            // Drop indexes
            $table->dropIndex(['project_owner_type', 'project_owner_id']);
            $table->dropIndex(['contract_id']);
            $table->dropIndex(['project_owner_id']);
            $table->dropIndex(['project_owner_type']);
            $table->dropIndex(['branch_id']);

            // Drop new columns
            $table->dropColumn(['contract_id', 'project_owner_id', 'project_owner_type', 'branch_id']);

            // Rename manager_id back to responsible_employee_id
            $table->renameColumn('manager_id', 'responsible_employee_id');
        });
    }
};
