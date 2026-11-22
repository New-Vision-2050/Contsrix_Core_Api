<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('details')->nullable();
            $table->string('visibility', 20)->default('company');
            $table->json('branch_ids')->nullable();
            $table->json('project_ids')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('archived_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->index('company_id', 'forums_company_idx');
            $table->index(['company_id', 'status'], 'forums_company_status_idx');
        });

        Schema::create('forum_admins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('forum_id');
            $table->uuid('user_id');
            $table->boolean('can_manage_groups')->default(true);
            $table->boolean('can_publish_announcements')->default(true);
            $table->timestamps();

            $table->unique(['forum_id', 'user_id'], 'forum_admins_forum_user_unique');
            $table->index('user_id', 'forum_admins_user_idx');
            $table->foreign('forum_id')->references('id')->on('forums')->cascadeOnDelete();
        });

        Schema::create('forum_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('forum_id');
            $table->uuid('user_id');
            $table->timestamps();

            $table->unique(['forum_id', 'user_id'], 'forum_members_forum_user_unique');
            $table->index('user_id', 'forum_members_user_idx');
            $table->foreign('forum_id')->references('id')->on('forums')->cascadeOnDelete();
        });

        Schema::create('forum_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('forum_id');
            $table->uuid('group_id');
            $table->uuid('message_id');
            $table->uuid('reporter_id');
            $table->text('reason');
            $table->string('status', 20)->default('new');
            $table->text('resolution_note')->nullable();
            $table->uuid('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'reporter_id'], 'forum_reports_message_reporter_unique');
            $table->index(['forum_id', 'status'], 'forum_reports_forum_status_idx');
            $table->foreign('forum_id')->references('id')->on('forums')->cascadeOnDelete();
        });

        Schema::create('forum_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('forum_id');
            $table->uuid('actor_id')->nullable();
            $table->string('action', 60);
            $table->string('target_type', 30)->nullable();
            $table->uuid('target_id')->nullable();
            $table->string('target_label')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamps();

            $table->index(['forum_id', 'created_at'], 'forum_activity_forum_created_idx');
            $table->foreign('forum_id')->references('id')->on('forums')->cascadeOnDelete();
        });

        Schema::table('forum_groups', function (Blueprint $table) {
            $table->uuid('forum_id')->nullable()->after('company_id');
            $table->string('status', 20)->default('active')->after('description');
            $table->string('category')->nullable()->after('status');
            $table->boolean('show_name_in_chat')->default(true)->after('category');
            $table->index('forum_id', 'forum_groups_forum_idx');
        });

        Schema::table('forum_announcements', function (Blueprint $table) {
            $table->uuid('forum_id')->nullable()->after('company_id');
            $table->string('status', 20)->default('published')->after('body');
            $table->boolean('notify')->default(true)->after('status');
            $table->index('forum_id', 'forum_announcements_forum_idx');
        });

        $companyIds = DB::table('forum_groups')->distinct()->pluck('company_id')
            ->merge(DB::table('forum_announcements')->distinct()->pluck('company_id'))
            ->filter()
            ->unique()
            ->values();

        foreach ($companyIds as $companyId) {
            $forumId = (string) Str::uuid();
            DB::table('forums')->insert([
                'id' => $forumId,
                'company_id' => $companyId,
                'name' => 'المنتدى',
                'visibility' => 'company',
                'status' => 'active',
                'branch_ids' => json_encode([]),
                'project_ids' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('forum_groups')->where('company_id', $companyId)->whereNull('forum_id')->update(['forum_id' => $forumId]);
            DB::table('forum_announcements')->where('company_id', $companyId)->whereNull('forum_id')->update([
                'forum_id' => $forumId,
                'status' => 'published',
            ]);
        }

        Schema::table('forum_groups', function (Blueprint $table) {
            $table->foreign('forum_id')->references('id')->on('forums')->nullOnDelete();
        });
        Schema::table('forum_announcements', function (Blueprint $table) {
            $table->foreign('forum_id')->references('id')->on('forums')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('forum_announcements', function (Blueprint $table) {
            $table->dropForeign(['forum_id']);
            $table->dropIndex('forum_announcements_forum_idx');
            $table->dropColumn(['forum_id', 'status', 'notify']);
        });

        Schema::table('forum_groups', function (Blueprint $table) {
            $table->dropForeign(['forum_id']);
            $table->dropIndex('forum_groups_forum_idx');
            $table->dropColumn(['forum_id', 'status', 'category', 'show_name_in_chat']);
        });

        Schema::dropIfExists('forum_activity_logs');
        Schema::dropIfExists('forum_reports');
        Schema::dropIfExists('forum_members');
        Schema::dropIfExists('forum_admins');
        Schema::dropIfExists('forums');
    }
};
