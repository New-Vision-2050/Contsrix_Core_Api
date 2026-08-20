<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Project\ProjectManagement\Enums\ProjectReportCode;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects') || Schema::hasColumn('projects', 'code_report')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->enum('code_report', ProjectReportCode::values())
                ->default(ProjectReportCode::default()->value)
                ->after('serial_number');
        });

        DB::table('projects')
            ->whereNull('code_report')
            ->update(['code_report' => ProjectReportCode::default()->value]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('projects') || ! Schema::hasColumn('projects', 'code_report')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('code_report');
        });
    }
};
