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
            $table->uuid('notify_by_sms')->default(false)->after('notify_by_whatsapp')->index();
                $table->unsignedSmallInteger('auto_approval_within_hours')->nullable()->after('notify_by_sms');

        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('procedure_setting_steps')) {
            return;
        }

        Schema::table('procedure_setting_steps', function (Blueprint $table) {
            $table->dropColumn('notify_by_sms');
            $table->dropColumn('auto_approval_within_hours');
        });
    }
};
