<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_notification_site_status_updates', function (Blueprint $table) {
            $table->text('description')->nullable()->after('update_time');
        });
    }

    public function down(): void
    {
        Schema::table('project_notification_site_status_updates', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
