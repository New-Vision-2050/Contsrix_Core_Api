<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            if (! Schema::hasColumn('folders', 'employee_global_id')) {
                $table->uuid('employee_global_id')->nullable()->after('type')->index();
            }
        });

        Schema::table('files', function (Blueprint $table) {
            if (! Schema::hasColumn('files', 'type')) {
                $table->string('type')->nullable()->after('management_hierarchy_id')->index();
            }

            if (! Schema::hasColumn('files', 'employee_global_id')) {
                $table->uuid('employee_global_id')->nullable()->after('type')->index();
            }

            if (! Schema::hasColumn('files', 'employee_section')) {
                $table->string('employee_section')->nullable()->after('employee_global_id');
            }

            if (! Schema::hasColumn('files', 'employee_sub_section')) {
                $table->string('employee_sub_section')->nullable()->after('employee_section');
            }

            if (! Schema::hasColumn('files', 'source_model_type')) {
                $table->string('source_model_type')->nullable()->after('employee_sub_section');
            }

            if (! Schema::hasColumn('files', 'source_model_id')) {
                $table->string('source_model_id')->nullable()->after('source_model_type')->index();
            }

            if (! Schema::hasColumn('files', 'source_media_id')) {
                $table->unsignedBigInteger('source_media_id')->nullable()->after('source_model_id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $columns = [
                'source_media_id',
                'source_model_id',
                'source_model_type',
                'employee_sub_section',
                'employee_section',
                'employee_global_id',
                'type',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('files', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('folders', function (Blueprint $table) {
            if (Schema::hasColumn('folders', 'employee_global_id')) {
                $table->dropColumn('employee_global_id');
            }
        });
    }
};
