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
        // Add indexes to project_permissions table
        Schema::table('project_permissions', function (Blueprint $table) {
            $table->index('name', 'idx_project_permissions_name');
            $table->index('submodule', 'idx_project_permissions_submodule');
            $table->index('action', 'idx_project_permissions_action');
            $table->index('is_active', 'idx_project_permissions_is_active');
            $table->index(['submodule', 'action'], 'idx_project_permissions_submodule_action');
        });

        // Add indexes to project_role_permissions table
        Schema::table('project_role_permissions', function (Blueprint $table) {
            $table->index('project_role_id', 'idx_project_role_permissions_role_id');
            $table->index('project_permission_id', 'idx_project_role_permissions_permission_id');
        });

        // Add indexes to project_employees table
        Schema::table('project_employees', function (Blueprint $table) {
            $table->index('project_id', 'idx_project_employees_project_id');
            $table->index('user_id', 'idx_project_employees_user_id');
            $table->index('project_role_id', 'idx_project_employees_role_id');
            $table->index(['project_id', 'user_id'], 'idx_project_employees_project_user');
        });

        // Add indexes to project_roles table
        Schema::table('project_roles', function (Blueprint $table) {
            $table->index('project_id', 'idx_project_roles_project_id');
            $table->index('slug', 'idx_project_roles_slug');
            $table->index('is_active', 'idx_project_roles_is_active');
            $table->index('is_default', 'idx_project_roles_is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_permissions', function (Blueprint $table) {
            $table->dropIndex('idx_project_permissions_name');
            $table->dropIndex('idx_project_permissions_submodule');
            $table->dropIndex('idx_project_permissions_action');
            $table->dropIndex('idx_project_permissions_is_active');
            $table->dropIndex('idx_project_permissions_submodule_action');
        });

        Schema::table('project_role_permissions', function (Blueprint $table) {
            $table->dropIndex('idx_project_role_permissions_role_id');
            $table->dropIndex('idx_project_role_permissions_permission_id');
        });

        Schema::table('project_employees', function (Blueprint $table) {
            $table->dropIndex('idx_project_employees_project_id');
            $table->dropIndex('idx_project_employees_user_id');
            $table->dropIndex('idx_project_employees_role_id');
            $table->dropIndex('idx_project_employees_project_user');
        });

        Schema::table('project_roles', function (Blueprint $table) {
            $table->dropIndex('idx_project_roles_project_id');
            $table->dropIndex('idx_project_roles_slug');
            $table->dropIndex('idx_project_roles_is_active');
            $table->dropIndex('idx_project_roles_is_default');
        });
    }
};
