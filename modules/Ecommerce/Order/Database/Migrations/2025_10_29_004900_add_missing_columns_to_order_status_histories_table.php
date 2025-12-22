<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_10_29_004900_add_missing_columns_to_order_status_histories_table  Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_status_histories', function (Blueprint $table) {
            // Add missing columns
            $table->string('previous_status', 191)->nullable()->after('status');
            $table->uuid('changed_by')->nullable()->after('previous_status');
            $table->string('reason', 255)->nullable()->after('changed_by');
            $table->text('notes')->nullable()->after('reason');
            $table->timestamp('changed_at')->nullable()->after('notes');
            
            // Add new indexes
            $table->index(['changed_by']);
            $table->index(['previous_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_status_histories', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['changed_by']);
            $table->dropIndex(['previous_status']);
            
            // Drop columns
            $table->dropColumn([
                'previous_status',
                'changed_by', 
                'reason',
                'notes',
                'changed_at'
            ]);
        });
    }
};
