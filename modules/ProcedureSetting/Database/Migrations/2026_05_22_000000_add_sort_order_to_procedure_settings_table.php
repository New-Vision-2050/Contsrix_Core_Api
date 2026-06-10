<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        public function up(): void
        {
            if (!Schema::hasTable('procedure_settings')) {
                return;
            }

            if (!Schema::hasColumn('procedure_settings', 'sort_order')) {
                Schema::table('procedure_settings', function (Blueprint $table) {
                    $table->unsignedInteger('sort_order')
                        ->nullable()
                        ->after('work_flow_id')
                        ->index();
                });
            }
        }

        public function down(): void
        {
            if (!Schema::hasTable('procedure_settings')) {
                return;
            }

            if (Schema::hasColumn('procedure_settings', 'sort_order')) {
                Schema::table('procedure_settings', function (Blueprint $table) {
                    $table->dropColumn('sort_order');
                });
            }
        }
};
