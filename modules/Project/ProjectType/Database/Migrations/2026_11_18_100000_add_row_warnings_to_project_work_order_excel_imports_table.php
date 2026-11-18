<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('project_work_order_excel_imports')) {
            return;
        }

        if (Schema::hasColumn('project_work_order_excel_imports', 'row_warnings')) {
            return;
        }

        Schema::table('project_work_order_excel_imports', function (Blueprint $table) {
            $table->json('row_warnings')->nullable()->after('row_errors');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_work_order_excel_imports')) {
            return;
        }

        if (! Schema::hasColumn('project_work_order_excel_imports', 'row_warnings')) {
            return;
        }

        Schema::table('project_work_order_excel_imports', function (Blueprint $table) {
            $table->dropColumn('row_warnings');
        });
    }
};
