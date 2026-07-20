<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Alter order_permit table: drop project_type_id, add uds_period and order_permit_department_id
        Schema::table('order_permit', function (Blueprint $table) {
            if (Schema::hasColumn('order_permit', 'project_type_id')) {
                $table->dropForeign(['project_type_id']);
                $table->dropColumn('project_type_id');
            }
        });

        Schema::table('order_permit', function (Blueprint $table) {
            if (!Schema::hasColumn('order_permit', 'uds_period')) {
                $table->decimal('uds_period', 8, 2)->nullable()->after('type');
            }
        });

        Schema::table('order_permit', function (Blueprint $table) {
            if (!Schema::hasColumn('order_permit', 'order_permit_department_id')) {
                $table->unsignedBigInteger('order_permit_department_id')->nullable()->after('uds_period');

                $table->foreign('order_permit_department_id')
                    ->references('id')
                    ->on('order_permit_department')
                    ->onDelete('set null');
            }
        });

        // Alter order_permit_department table: drop all columns except id, add name
        Schema::table('order_permit_department', function (Blueprint $table) {
            if (Schema::hasColumn('order_permit_department', 'order_permit_id')) {
                $table->dropForeign(['order_permit_id']);
                $table->dropColumn('order_permit_id');
            }
        });

        Schema::table('order_permit_department', function (Blueprint $table) {
            if (Schema::hasColumn('order_permit_department', 'project_type_id')) {
                $table->dropForeign(['project_type_id']);
                $table->dropColumn('project_type_id');
            }
        });

        Schema::table('order_permit_department', function (Blueprint $table) {
            if (Schema::hasColumn('order_permit_department', 'code')) {
                $table->dropColumn('code');
            }
        });

        Schema::table('order_permit_department', function (Blueprint $table) {
            if (Schema::hasColumn('order_permit_department', 'description')) {
                $table->dropColumn('description');
            }
        });

        Schema::table('order_permit_department', function (Blueprint $table) {
            if (!Schema::hasColumn('order_permit_department', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
        });
    }

    public function down(): void
    {
        // Revert order_permit_department
        Schema::table('order_permit_department', function (Blueprint $table) {
            if (Schema::hasColumn('order_permit_department', 'name')) {
                $table->dropColumn('name');
            }
        });

        Schema::table('order_permit_department', function (Blueprint $table) {
            if (!Schema::hasColumn('order_permit_department', 'description')) {
                $table->string('description', 255)->nullable()->after('id');
            }
        });

        Schema::table('order_permit_department', function (Blueprint $table) {
            if (!Schema::hasColumn('order_permit_department', 'code')) {
                $table->string('code', 255)->nullable()->after('id');
            }
        });

        Schema::table('order_permit_department', function (Blueprint $table) {
            if (!Schema::hasColumn('order_permit_department', 'project_type_id')) {
                $table->unsignedBigInteger('project_type_id')->after('id');

                $table->foreign('project_type_id')
                    ->references('id')
                    ->on('project_types')
                    ->onDelete('cascade');
            }
        });

        Schema::table('order_permit_department', function (Blueprint $table) {
            if (!Schema::hasColumn('order_permit_department', 'order_permit_id')) {
                $table->unsignedBigInteger('order_permit_id')->nullable()->after('project_type_id');

                $table->foreign('order_permit_id')
                    ->references('id')
                    ->on('order_permit')
                    ->onDelete('set null');
            }
        });

        // Revert order_permit
        Schema::table('order_permit', function (Blueprint $table) {
            if (Schema::hasColumn('order_permit', 'order_permit_department_id')) {
                $table->dropForeign(['order_permit_department_id']);
                $table->dropColumn('order_permit_department_id');
            }
        });

        Schema::table('order_permit', function (Blueprint $table) {
            if (Schema::hasColumn('order_permit', 'uds_period')) {
                $table->dropColumn('uds_period');
            }
        });

        Schema::table('order_permit', function (Blueprint $table) {
            if (!Schema::hasColumn('order_permit', 'project_type_id')) {
                $table->unsignedBigInteger('project_type_id')->after('id');

                $table->foreign('project_type_id')
                    ->references('id')
                    ->on('project_types')
                    ->onDelete('cascade');
            }
        });
    }
};
