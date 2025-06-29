<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing module_id foreign key and column if they exist
        if (Schema::hasColumn('program_system_feature', 'module_id')) {
            Schema::table('program_system_feature', function (Blueprint $table) {
                $table->dropForeign(['module_id']);
            });

            Schema::table('program_system_feature', function (Blueprint $table) {
                $table->dropColumn('module_id');
            });
        }

        // Add program_id with foreign key to programs
        Schema::table('program_system_feature', function (Blueprint $table) {
            $table->uuid('program_id')->after('feature_id');
            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('program_system_feature', function (Blueprint $table) {
            if (Schema::hasColumn('program_system_feature', 'program_id')) {
                $table->dropForeign(['program_id']);
                $table->dropColumn('program_id');
            }

            $table->uuid('module_id')->after('feature_id');
            $table->foreign('module_id')->references('id')->on('modules')->cascadeOnDelete();
        });
    }
};
