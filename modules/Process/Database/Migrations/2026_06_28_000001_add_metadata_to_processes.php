<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add metadata JSON column to processes table.
 *
 * Stores arbitrary action payload for lifecycle processes (e.g. start/end
 * latitude, longitude, notes) so the business logic can be executed later
 * when the process completes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('processes', function (Blueprint $table): void {
            $table->json('metadata')
                ->nullable()
                ->after('template_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('processes', function (Blueprint $table): void {
            $table->dropColumn('metadata');
        });
    }
};
