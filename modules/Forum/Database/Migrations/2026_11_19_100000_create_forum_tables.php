<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('send_permission', 30)->default('all_members');
            $table->boolean('allow_images')->default(true);
            $table->boolean('allow_voice')->default(true);
            $table->boolean('allow_files')->default(true);
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->index('company_id', 'forum_groups_company_idx');
        });

        Schema::create('forum_group_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('group_id');
            $table->uuid('user_id');
            $table->string('role', 20)->default('member');
            $table->boolean('can_send')->default(false);
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'user_id'], 'forum_members_group_user_unique');
            $table->index('user_id', 'forum_members_user_idx');
            $table->foreign('group_id')->references('id')->on('forum_groups')->onDelete('cascade');
        });

        Schema::create('forum_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('group_id');
            $table->uuid('sender_id');
            $table->string('type', 20)->default('text');
            $table->text('body')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();

            $table->index(['group_id', 'created_at'], 'forum_messages_group_created_idx');
            $table->index('company_id', 'forum_messages_company_idx');
            $table->foreign('group_id')->references('id')->on('forum_groups')->onDelete('cascade');
        });

        Schema::create('forum_announcements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('title')->nullable();
            $table->text('body');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'created_at'], 'forum_announcements_company_idx');
        });

        Schema::create('forum_announcement_reads', function (Blueprint $table) {
            $table->uuid('announcement_id');
            $table->uuid('user_id');
            $table->timestamp('read_at')->useCurrent();

            $table->primary(['announcement_id', 'user_id']);
            $table->foreign('announcement_id')->references('id')->on('forum_announcements')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_announcement_reads');
        Schema::dropIfExists('forum_announcements');
        Schema::dropIfExists('forum_messages');
        Schema::dropIfExists('forum_group_members');
        Schema::dropIfExists('forum_groups');
    }
};
