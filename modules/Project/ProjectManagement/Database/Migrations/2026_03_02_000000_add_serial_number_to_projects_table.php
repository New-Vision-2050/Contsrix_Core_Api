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
        Schema::table('projects', function (Blueprint $table) {
            // Add serial_number column as string to support alphanumeric format (e.g., PRJ-0001)
            $table->string('serial_number')->nullable()->after('id');
        });

        // Update existing records to have unique serial numbers
        DB::statement("
            UPDATE projects p1 
            JOIN (
                SELECT id, ROW_NUMBER() OVER (ORDER BY created_at) as row_num
                FROM projects
                WHERE serial_number IS NULL OR serial_number = ''
            ) p2 ON p1.id = p2.id
            SET p1.serial_number = CONCAT('PRJ-', LPAD(p2.row_num, 4, '0'))
        ");

        // Now make the column unique and not nullable
        Schema::table('projects', function (Blueprint $table) {
            $table->string('serial_number')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('serial_number');
        });
    }
};
