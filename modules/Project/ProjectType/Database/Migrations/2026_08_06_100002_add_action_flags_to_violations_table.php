<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('violations')) {
            return;
        }

        Schema::table('violations', function (Blueprint $table) {
            if (! Schema::hasColumn('violations', 'work_cancellation')) {
                $table->boolean('work_cancellation')->default(false)->after('default_weight');
            }

            if (! Schema::hasColumn('violations', 'work_stop')) {
                $table->boolean('work_stop')->default(false)->after('work_cancellation');
            }

            if (! Schema::hasColumn('violations', 'equipment_exclusion')) {
                $table->boolean('equipment_exclusion')->default(false)->after('work_stop');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('violations')) {
            return;
        }

        Schema::table('violations', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('violations', 'work_cancellation') ? 'work_cancellation' : null,
                Schema::hasColumn('violations', 'work_stop') ? 'work_stop' : null,
                Schema::hasColumn('violations', 'equipment_exclusion') ? 'equipment_exclusion' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
