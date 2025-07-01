<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        // Drop 'module_id' if exists
        if (Schema::hasColumn('features', 'module_id')) {
            Schema::table('features', function (Blueprint $table) {
                $table->dropForeign(['module_id']);
            });

            Schema::table('features', function (Blueprint $table) {
                $table->dropColumn('module_id');
            });
        }

        // Drop 'program_id' if exists
        if (Schema::hasColumn('features', 'program_id')) {
            Schema::table('features', function (Blueprint $table) {
                $table->dropForeign(['program_id']);
            });

            Schema::table('features', function (Blueprint $table) {
                $table->dropColumn('program_id');
            });
        }
    }

    public function down(): void
    {
        // Restore 'module_id'
        if (!Schema::hasColumn('features', 'module_id')) {
            Schema::table('features', function (Blueprint $table) {
                $table->uuid('module_id')->nullable()->after('slug');
                $table->foreign('module_id')->references('id')->on('modules')->cascadeOnDelete();
            });
        }

        // Restore 'program_id'
        if (!Schema::hasColumn('features', 'program_id')) {
            Schema::table('features', function (Blueprint $table) {
                $table->uuid('program_id')->after('slug');
                $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
            });
        }
    }
};
