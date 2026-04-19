<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resource_shares', function (Blueprint $table) {
            $table->unsignedBigInteger('type_id')->nullable()->after('shareable_id');
            $table->unsignedBigInteger('relation_id')->nullable()->after('type_id');
            $table->unsignedBigInteger('role_id')->nullable()->after('relation_id');

            $table->foreign('type_id')
                ->references('id')
                ->on('project_share_types')
                ->onDelete('set null');

            $table->foreign('relation_id')
                ->references('id')
                ->on('project_share_types')
                ->onDelete('set null');

            $table->foreign('role_id')
                ->references('id')
                ->on('project_share_types')
                ->onDelete('set null');

            $table->index('type_id');
            $table->index('relation_id');
            $table->index('role_id');
        });
    }

    public function down(): void
    {
        Schema::table('resource_shares', function (Blueprint $table) {
            $table->dropForeign(['type_id']);
            $table->dropForeign(['relation_id']);
            $table->dropForeign(['role_id']);
            $table->dropColumn(['type_id', 'relation_id', 'role_id']);
        });
    }
};
