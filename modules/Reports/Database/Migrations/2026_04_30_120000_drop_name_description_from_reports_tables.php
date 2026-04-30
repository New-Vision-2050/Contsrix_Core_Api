<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop name and description from reports table (handled by HasTranslations trait in separate table)
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        // Drop name and description from report_templates table (handled by HasTranslations trait in separate table)
        Schema::table('report_templates', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropColumn('description');
        });
    }

    public function down(): void
    {
        // Re-add name and description columns (for rollback)
        Schema::table('reports', function (Blueprint $table) {
            $table->json('name')->nullable();
        });

        Schema::table('report_templates', function (Blueprint $table) {
            $table->json('name');
            $table->json('description')->nullable();
        });
    }
};
