<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_task_requests', function (Blueprint $table) {
            $table->dateTime('location_confirmed_at')->nullable()->after('end_location');
        });
    }

    public function down(): void
    {
        Schema::table('employee_task_requests', function (Blueprint $table) {
            $table->dropColumn('location_confirmed_at');
        });
    }
};
