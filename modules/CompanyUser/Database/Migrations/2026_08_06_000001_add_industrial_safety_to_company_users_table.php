<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->string('industrial_safety')->nullable()->after('work_permit');
            $table->date('industrial_safety_start_date')->nullable()->after('industrial_safety');
            $table->date('industrial_safety_end_date')->nullable()->after('industrial_safety_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->dropColumn([
                'industrial_safety',
                'industrial_safety_start_date',
                'industrial_safety_end_date',
            ]);
        });
    }
};
