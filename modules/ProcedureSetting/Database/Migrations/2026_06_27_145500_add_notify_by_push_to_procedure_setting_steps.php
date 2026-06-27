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

        Schema::table('procedure_setting_steps', function (Blueprint $table) {
            if (! Schema::hasColumn('procedure_setting_steps', 'notify_by_push')) {
                $table->boolean('notify_by_push')->default(false)->after('notify_by_sms');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('procedure_setting_steps')) {
            return;
        }

        Schema::table('procedure_setting_steps', function (Blueprint $table) {
            if (Schema::hasColumn('procedure_setting_steps', 'notify_by_push')) {
                $table->dropColumn('notify_by_push');
            }
        });
    }
};
