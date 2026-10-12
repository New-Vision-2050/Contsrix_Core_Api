<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Links a leave request to the leave policy it consumes so balances can be
     * tracked per policy. Nullable to keep historical requests intact when a
     * policy is removed.
     */
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->uuid('leave_policy_id')->nullable()->after('leave_type_id');
            $table->index(['leave_policy_id']);
            $table->foreign('leave_policy_id')->references('id')->on('leave_policies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign(['leave_policy_id']);
            $table->dropIndex(['leave_policy_id']);
            $table->dropColumn('leave_policy_id');
        });
    }
};
