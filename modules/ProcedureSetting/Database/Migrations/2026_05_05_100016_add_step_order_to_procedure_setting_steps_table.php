<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('procedure_setting_steps')) {
            return;
        }

        if (! Schema::hasColumn('procedure_setting_steps', 'step_order')) {
            Schema::table('procedure_setting_steps', function (Blueprint $table) {
                $table->unsignedInteger('step_order')->nullable()->after('escalation_user_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('procedure_setting_steps')) {
            return;
        }

        if (Schema::hasColumn('procedure_setting_steps', 'step_order')) {
            Schema::table('procedure_setting_steps', function (Blueprint $table) {
                $table->dropColumn('step_order');
            });
        }
    }
};
