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
        // Add is_all_data_visible to roles_and_permissions_settings table
        if (Schema::hasTable('roles_and_permissions_settings') && !Schema::hasColumn('roles_and_permissions_settings', 'is_all_data_visible')) {
            Schema::table('roles_and_permissions_settings', function (Blueprint $table) {
                $table->boolean('is_all_data_visible')->default(0)->after('project_type_id');
            });
        }

        // Add is_all_data_visible to project_sharing_settings table
        if (Schema::hasTable('project_sharing_settings') && !Schema::hasColumn('project_sharing_settings', 'is_all_data_visible')) {
            Schema::table('project_sharing_settings', function (Blueprint $table) {
                $table->boolean('is_all_data_visible')->default(0)->after('project_type_id');
            });
        }

        // Drop is_enabled column from roles_and_permissions_settings if exists
        if (Schema::hasTable('roles_and_permissions_settings') && Schema::hasColumn('roles_and_permissions_settings', 'is_enabled')) {
            Schema::table('roles_and_permissions_settings', function (Blueprint $table) {
                $table->dropColumn('is_enabled');
            });
        }

        // Drop is_enabled column from project_sharing_settings if exists
        if (Schema::hasTable('project_sharing_settings') && Schema::hasColumn('project_sharing_settings', 'is_enabled')) {
            Schema::table('project_sharing_settings', function (Blueprint $table) {
                $table->dropColumn('is_enabled');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back is_enabled to roles_and_permissions_settings table
        if (Schema::hasTable('roles_and_permissions_settings') && !Schema::hasColumn('roles_and_permissions_settings', 'is_enabled')) {
            Schema::table('roles_and_permissions_settings', function (Blueprint $table) {
                $table->boolean('is_enabled')->default(0)->after('project_type_id');
            });
        }

        // Add back is_enabled to project_sharing_settings table
        if (Schema::hasTable('project_sharing_settings') && !Schema::hasColumn('project_sharing_settings', 'is_enabled')) {
            Schema::table('project_sharing_settings', function (Blueprint $table) {
                $table->boolean('is_enabled')->default(0)->after('project_type_id');
            });
        }

        // Drop is_all_data_visible from roles_and_permissions_settings if exists
        if (Schema::hasTable('roles_and_permissions_settings') && Schema::hasColumn('roles_and_permissions_settings', 'is_all_data_visible')) {
            Schema::table('roles_and_permissions_settings', function (Blueprint $table) {
                $table->dropColumn('is_all_data_visible');
            });
        }

        // Drop is_all_data_visible from project_sharing_settings if exists
        if (Schema::hasTable('project_sharing_settings') && Schema::hasColumn('project_sharing_settings', 'is_all_data_visible')) {
            Schema::table('project_sharing_settings', function (Blueprint $table) {
                $table->dropColumn('is_all_data_visible');
            });
        }
    }
};
