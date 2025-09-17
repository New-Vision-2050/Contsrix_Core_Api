<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Make clock_in_time nullable to accommodate 'waiting' status
            $table->timestamp('clock_in_time')->nullable()->change();

            // Change status from enum to string to allow for more flexibility
            $table->string('status', 191)->default('active')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Revert clock_in_time to not nullable
            $table->timestamp('clock_in_time')->nullable(false)->change();

            // Revert status to enum
            // Note: This assumes the original enum values. Data loss may occur if new string values were used.
            $table->enum('status', ['active', 'completed', 'pending_approval', 'approved', 'rejected', 'waiting', 'absent'])->default('active')->change();
        });
    }
};
