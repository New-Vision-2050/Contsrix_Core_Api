<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_order_permit', function (Blueprint $table) {
            if (!Schema::hasColumn('project_order_permit', 'project_management_id')) {
                $table->unsignedBigInteger('project_management_id')->nullable()->after('project_id');

                $table->foreign('project_management_id')
                    ->references('id')
                    ->on('project_managements')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_order_permit', function (Blueprint $table) {
            if (Schema::hasColumn('project_order_permit', 'project_management_id')) {
                $table->dropForeign(['project_management_id']);
                $table->dropColumn('project_management_id');
            }
        });
    }
};
