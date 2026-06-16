<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procedure_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('procedure_settings', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('form');
            }
        });
    }

    public function down(): void
    {
        Schema::table('procedure_settings', function (Blueprint $table) {
            if (Schema::hasColumn('procedure_settings', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
