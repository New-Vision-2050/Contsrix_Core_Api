<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_notifications', function (Blueprint $table) {
            $table->string('machine_number')->nullable()->after('feeder_number');
            $table->string('permit_source')->nullable()->after('repair_point');
            $table->string('permit_recipient')->nullable()->after('permit_source');
        });
    }

    public function down(): void
    {
        Schema::table('project_notifications', function (Blueprint $table) {
            $table->dropColumn(['machine_number', 'permit_source', 'permit_recipient']);
        });
    }
};
