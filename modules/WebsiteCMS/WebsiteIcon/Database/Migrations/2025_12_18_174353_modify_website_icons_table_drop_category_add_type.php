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
        Schema::table('website_icons', function (Blueprint $table) {
            // Check if foreign key exists and drop it
            if ($this->foreignKeyExists('website_icons', 'website_icons_category_website_cms_id_foreign')) {
                $table->dropForeign(['category_website_cms_id']);
            }

            // Check if column exists and drop it
            if (Schema::hasColumn('website_icons', 'category_website_cms_id')) {
                $table->dropColumn('category_website_cms_id');
            }

            // Add new string column for category type if not exists
            if (!Schema::hasColumn('website_icons', 'website_icon_category_type')) {
                $table->string('website_icon_category_type')->nullable()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_icons', function (Blueprint $table) {
            // Remove the new column if exists
            if (Schema::hasColumn('website_icons', 'website_icon_category_type')) {
                $table->dropColumn('website_icon_category_type');
            }

            // Re-add the original column with foreign key if not exists
            if (!Schema::hasColumn('website_icons', 'category_website_cms_id')) {
                $table->uuid('category_website_cms_id')->nullable()->after('id');
                $table->foreign('category_website_cms_id')
                    ->references('id')
                    ->on('category_website_cms')
                    ->onDelete('cascade');
            }
        });
    }

    /**
     * Check if a foreign key exists on a table.
     */
    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        $database = config('database.connections.mysql.database');
        
        $result = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_TYPE = 'FOREIGN KEY' 
            AND TABLE_SCHEMA = ? 
            AND TABLE_NAME = ? 
            AND CONSTRAINT_NAME = ?
        ", [$database, $table, $foreignKey]);

        return count($result) > 0;
    }
};
