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
            if (Schema::hasColumn('procedure_setting_steps', 'skipping_period')) {
                $table->decimal('skipping_period', 10, 4)
                    ->nullable()
                    ->default(null)
                    ->comment('Auto-approve after N hours (fractional hours allowed, e.g. 0.0167 = 1 minute) if requires_approval_within_period is true')
                    ->change();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('procedure_setting_steps')) {
            return;
        }

        Schema::table('procedure_setting_steps', function (Blueprint $table) {
            if (Schema::hasColumn('procedure_setting_steps', 'skipping_period')) {
                $table->integer('skipping_period')
                    ->nullable()
                    ->comment('Auto-approve after N hours if requires_approval_within_period is true')
                    ->change();
            }
        });
    }
};
