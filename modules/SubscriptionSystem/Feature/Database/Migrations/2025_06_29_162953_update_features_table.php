<?php 
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('features', 'module_id')) {
            Schema::table('features', function (Blueprint $table) {
                // Drop the foreign key first!
                $table->dropForeign(['module_id']);
            });

            Schema::table('features', function (Blueprint $table) {
                // Now drop the column
                $table->dropColumn('module_id');
            });
        }

        Schema::table('features', function (Blueprint $table) {
            $table->uuid('program_id')->after('slug');
            $table->foreign('program_id')->references('id')->on('programs')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            if (Schema::hasColumn('features', 'program_id')) {
                $table->dropForeign(['program_id']);
                $table->dropColumn('program_id');
            }

            $table->uuid('module_id')->nullable()->after('slug');
            $table->foreign('module_id')->references('id')->on('modules')->cascadeOnDelete();
        });
    }
};
