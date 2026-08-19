<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_contractors', function (Blueprint $table) {
            $table->string('safety_officer_name')->nullable()->after('project_manager_email');
            $table->string('safety_officer_email')->nullable()->after('safety_officer_name');
        });
    }

    public function down(): void
    {
        Schema::table('project_contractors', function (Blueprint $table) {
            $table->dropColumn(['safety_officer_name', 'safety_officer_email']);
        });
    }
};