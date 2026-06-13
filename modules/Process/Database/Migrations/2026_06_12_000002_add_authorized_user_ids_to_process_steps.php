<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('process_steps')) {
            return;
        }

        if (! Schema::hasColumn('process_steps', 'authorized_user_ids')) {
            Schema::table('process_steps', function (Blueprint $table) {
                $table->json('authorized_user_ids')->nullable()->after('assigned_user_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('process_steps')) {
            return;
        }

        if (Schema::hasColumn('process_steps', 'authorized_user_ids')) {
            Schema::table('process_steps', function (Blueprint $table) {
                $table->dropColumn('authorized_user_ids');
            });
        }
    }
};
