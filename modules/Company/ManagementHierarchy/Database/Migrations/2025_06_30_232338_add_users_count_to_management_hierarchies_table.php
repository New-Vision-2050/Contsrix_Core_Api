<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('management_hierarchies', function (Blueprint $table) {
            $table->integer('users_count')->default(0)->after('type');
            $table->index('users_count');
        });

        // Initialize users_count with correct recursive values using raw DB queries
        // to avoid model attribute issues during migration
        $this->initializeUsersCount();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('management_hierarchies', function (Blueprint $table) {
            $table->dropIndex(['users_count']);
            $table->dropColumn('users_count');
        });
    }

    /**
     * Initialize users_count for existing records with recursive counting using raw queries
     */
    private function initializeUsersCount(): void
    {
        // Use raw SQL to avoid model attribute issues during migration
        // Get all management hierarchies ordered by path length (deepest first)
        $hierarchies = DB::select("
            SELECT id, path
            FROM management_hierarchies 
            ORDER BY CHAR_LENGTH(path) DESC
        ");

        foreach ($hierarchies as $hierarchy) {
            // For each hierarchy, find all descendants (including self) using path matching
            // The path-based approach allows us to find all descendants efficiently
            $selfAndDescendantIds = DB::select("
                SELECT id 
                FROM management_hierarchies 
                WHERE path LIKE CONCAT(?, '%') OR id = ?
            ", [$hierarchy->path . '/', $hierarchy->id]);
            
            $hierarchyIds = array_column($selfAndDescendantIds, 'id');
            
            if (!empty($hierarchyIds)) {
                // Count all users in this hierarchy tree
                $usersCount = DB::table('users')
                    ->whereIn('management_hierarchy_id', $hierarchyIds)
                    ->whereNull('deleted_at')
                    ->count();
                
                // Update the users_count using raw SQL
                DB::table('management_hierarchies')
                    ->where('id', $hierarchy->id)
                    ->update(['users_count' => $usersCount]);
            }
        }
    }
};
