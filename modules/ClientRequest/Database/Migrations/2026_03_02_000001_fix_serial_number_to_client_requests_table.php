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
        // First drop the existing column if it exists
        if (Schema::hasColumn('client_requests', 'serial_number')) {
            Schema::table('client_requests', function (Blueprint $table) {
                $table->dropColumn('serial_number');
            });
        }

        // Add the column again as nullable
        Schema::table('client_requests', function (Blueprint $table) {
            $table->string('serial_number')->nullable()->after('id');
        });

        // Update existing records to have unique serial numbers
        DB::statement("
            UPDATE client_requests cr1 
            JOIN (
                SELECT id, ROW_NUMBER() OVER (ORDER BY created_at) as row_num
                FROM client_requests
                WHERE serial_number IS NULL OR serial_number = ''
            ) cr2 ON cr1.id = cr2.id
            SET cr1.serial_number = CONCAT('REQ-', LPAD(cr2.row_num, 4, '0'))
        ");

        // Now make the column unique and not nullable
        Schema::table('client_requests', function (Blueprint $table) {
            $table->string('serial_number')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_requests', function (Blueprint $table) {
            $table->dropColumn('serial_number');
        });
    }
};
