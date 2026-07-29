<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_order_permit', function (Blueprint $table) {
            $table->text('note_from_departments_to_permit')->nullable()->after('note_from_permit_to_departments');
        });

        Schema::table('project_order_permit_note_logs', function (Blueprint $table) {
            $table->string('type')->default('permit_to_departments')->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('project_order_permit', function (Blueprint $table) {
            $table->dropColumn('note_from_departments_to_permit');
        });

        Schema::table('project_order_permit_note_logs', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
