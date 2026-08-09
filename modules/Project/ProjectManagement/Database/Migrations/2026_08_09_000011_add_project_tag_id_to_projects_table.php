<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects') || ! Schema::hasTable('project_tags')) {
            return;
        }

        if (Schema::hasColumn('projects', 'project_tag_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->uuid('project_tag_id')->nullable()->after('contractual_engagement_id');

            $table->foreign('project_tag_id')
                ->references('id')
                ->on('project_tags')
                ->onDelete('set null');

            $table->index('project_tag_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('projects') || ! Schema::hasColumn('projects', 'project_tag_id')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['project_tag_id']);
            $table->dropIndex(['project_tag_id']);
            $table->dropColumn('project_tag_id');
        });
    }
};
