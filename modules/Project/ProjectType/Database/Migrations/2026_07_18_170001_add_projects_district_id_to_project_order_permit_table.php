<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_order_permit', function (Blueprint $table) {
            if (!Schema::hasColumn('project_order_permit', 'projects_district_id')) {
                $table->unsignedBigInteger('projects_district_id')->nullable()->after('project_management_id');

                $table->foreign('projects_district_id')
                    ->references('id')
                    ->on('projects_districts')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_order_permit', function (Blueprint $table) {
            if (Schema::hasColumn('project_order_permit', 'projects_district_id')) {
                $table->dropForeign(['projects_district_id']);
                $table->dropColumn('projects_district_id');
            }
        });
    }
};
