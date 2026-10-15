<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Assigns one or more leave policies to a user. Every user gets the company
     * default policy (seeded 21-day "Annual Year") and admins can attach any
     * additional policies. Balances are computed live per calendar year.
     */
    public function up(): void
    {
        Schema::create('user_leave_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('leave_policy_id');
            $table->uuid('company_id');
            $table->boolean('is_default')->default(false);
            $table->uuid('assigned_by')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'leave_policy_id'], 'user_leave_policies_unique');
            $table->index(['company_id']);
            $table->index(['user_id']);
            $table->index(['leave_policy_id']);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('leave_policy_id')->references('id')->on('leave_policies')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('assigned_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_leave_policies');
    }
};
