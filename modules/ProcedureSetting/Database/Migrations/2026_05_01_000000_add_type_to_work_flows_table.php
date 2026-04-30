<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('work_flows') || Schema::hasColumn('work_flows', 'type')) {
            return;
        }

        Schema::table('work_flows', function (Blueprint $table) {
            $table->string('type')->default('client_request')->after('name');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('work_flows') || ! Schema::hasColumn('work_flows', 'type')) {
            return;
        }

        Schema::table('work_flows', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
