<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Assigns one or more advance policies to a user (synced from the user
     * profile). Same mechanics as user_leave_policies.
     */
    public function up(): void
    {
        Schema::create('user_advance_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('advance_policy_id');
            $table->uuid('company_id');
            $table->boolean('is_default')->default(false);
            $table->uuid('assigned_by')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'advance_policy_id'], 'user_advance_policies_unique');
            $table->index(['company_id']);
            $table->index(['user_id']);
            $table->index(['advance_policy_id']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('advance_policy_id')->references('id')->on('advance_policies')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_advance_policies');
    }
};
