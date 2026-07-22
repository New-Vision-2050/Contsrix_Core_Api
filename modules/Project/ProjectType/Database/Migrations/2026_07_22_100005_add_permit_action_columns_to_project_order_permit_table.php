<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_order_permit', function (Blueprint $table) {
            $table->date('start_permit_date')->nullable()->after('connection_phase_status_id');
            $table->date('end_permit_date')->nullable()->after('start_permit_date');
            $table->text('note_from_permit_to_departments')->nullable()->after('end_permit_date');
            $table->boolean('is_taked_action')->default(false)->after('note_from_permit_to_departments');
        });
    }

    public function down(): void
    {
        Schema::table('project_order_permit', function (Blueprint $table) {
            $table->dropColumn(['start_permit_date', 'end_permit_date', 'note_from_permit_to_departments', 'is_taked_action']);
        });
    }
};
