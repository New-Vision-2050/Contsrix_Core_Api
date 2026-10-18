<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_policies', function (Blueprint $table) {
            $table->uuid('leave_category_id')->nullable()->after('leave_type_id');
            $table->foreign('leave_category_id')
                ->references('id')
                ->on('leave_policy_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leave_policies', function (Blueprint $table) {
            $table->dropForeign(['leave_category_id']);
            $table->dropColumn('leave_category_id');
        });
    }
};
