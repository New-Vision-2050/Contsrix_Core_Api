<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('safety_record_violation', 'employee_id')) {
            Schema::table('safety_record_violation', function (Blueprint $table) {
                $table->string('status', 30)->nullable()->default('not_applicable'); // violation_found, no_violation, not_applicable
            });
        }

    }

    public function down(): void
    {
        Schema::table('safety_record_violation', function (Blueprint $table) {
            $table->dropColumn([
                'status',

            ]);
        });
    }
};
