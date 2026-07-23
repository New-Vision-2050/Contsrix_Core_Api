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

        if (! Schema::hasColumn('procedure_setting_steps', 'receiver_company_ids')) {
            Schema::table('procedure_setting_steps', function (Blueprint $table): void {
                $table->text('receiver_company_ids')
                    ->nullable()
                    ->after('action_taker_management_hierarchies');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('procedure_setting_steps')) {
            return;
        }

        if (Schema::hasColumn('procedure_setting_steps', 'receiver_company_ids')) {
            Schema::table('procedure_setting_steps', function (Blueprint $table): void {
                $table->dropColumn('receiver_company_ids');
            });
        }
    }
};
