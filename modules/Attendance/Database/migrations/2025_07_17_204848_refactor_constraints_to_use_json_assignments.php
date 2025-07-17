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
        // Add the new JSON columns first. This is a safe operation.
        Schema::table('attendance_constraints', function (Blueprint $table) {
            $table->json('user_ids')->nullable()->after('company_id');
            $table->json('department_ids')->nullable()->after('user_ids');
        });

        // Migrate the data from the old columns to the new JSON columns.
        DB::table('attendance_constraints')
            ->whereNotNull('user_id')
            ->select('id', 'user_id')
            ->chunkById(100, function ($constraints) {
                foreach ($constraints as $constraint) {
                    DB::table('attendance_constraints')
                        ->where('id', $constraint->id)
                        ->update(['user_ids' => json_encode([(string)$constraint->user_id])]);
                }
            });

        // Do the same for department_id if it has data.
        Schema::table('attendance_constraints', function (Blueprint $table) {
            $table->dropForeign('attendance_constraints_user_id_foreign');

            $table->dropColumn('user_id');
            $table->dropColumn('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // To reverse, we add the columns back, then drop the new ones.
        Schema::table('attendance_constraints', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->after('company_id');
             $table->uuid('department_id')->nullable()->after('user_id');
        });

        // Attempt to move data back.
        DB::statement("UPDATE attendance_constraints SET user_id = JSON_UNQUOTE(JSON_EXTRACT(user_ids, '$[0]')) WHERE user_ids IS NOT NULL AND JSON_LENGTH(user_ids) > 0");

        Schema::table('attendance_constraints', function (Blueprint $table) {
            // Drop the new JSON columns
            $table->dropColumn(['user_ids', 'department_ids']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
