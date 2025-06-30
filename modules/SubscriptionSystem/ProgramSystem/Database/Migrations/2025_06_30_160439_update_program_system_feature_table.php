<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('program_system_feature', function (Blueprint $table) {
            if (Schema::hasColumn('program_system_feature', 'module_id')) {
                $table->dropForeign(['module_id']);
                $table->dropColumn('module_id');
            }

            if (Schema::hasColumn('program_system_feature', 'program_id')) {
                $table->dropForeign(['program_id']);
                $table->dropColumn('program_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('program_system_feature', function (Blueprint $table) {
            $table->foreignUuid('module_id')->constrained('modules')->cascadeOnDelete();
            $table->foreignUuid('program_id')->constrained('programs')->cascadeOnDelete();
        });
    }
};
