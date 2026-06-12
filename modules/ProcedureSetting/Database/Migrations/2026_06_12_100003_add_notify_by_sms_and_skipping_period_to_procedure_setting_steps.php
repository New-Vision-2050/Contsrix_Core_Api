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
            if (! Schema::hasColumn('procedure_setting_steps', 'notify_by_sms')) {
                $table->boolean('notify_by_sms')->default(false)->after('notify_by_whatsapp');
            }

            if (! Schema::hasColumn('procedure_setting_steps', 'skipping_period')) {
                $table->integer('skipping_period')->nullable()->after('approval_within_hours')->comment('Auto-approve after N hours if requires_approval_within_period is true');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('procedure_setting_steps')) {
            return;
        }

        Schema::table('procedure_setting_steps', function (Blueprint $table) {
            if (Schema::hasColumn('procedure_setting_steps', 'notify_by_sms')) {
                $table->dropColumn('notify_by_sms');
            }

            if (Schema::hasColumn('procedure_setting_steps', 'skipping_period')) {
                $table->dropColumn('skipping_period');
            }
        });
    }
};
