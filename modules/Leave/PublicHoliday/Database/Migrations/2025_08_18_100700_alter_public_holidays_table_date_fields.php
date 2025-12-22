<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_08_18_100700_alter_public_holidays_table_date_fields Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('public_holidays', function (Blueprint $table) {
            // Drop the existing date column and its index
            $table->dropIndex(['country_id', 'date']);
            $table->dropColumn('date');
            
            // Add new date_start and date_end columns
            $table->date('date_start');
            $table->date('date_end');
            
            // Add new indexes
            $table->index(['country_id', 'date_start', 'date_end']);
            $table->index(['date_start', 'date_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('public_holidays', function (Blueprint $table) {
            // Drop new columns and indexes
            $table->dropIndex(['country_id', 'date_start', 'date_end']);
            $table->dropIndex(['date_start', 'date_end']);
            $table->dropColumn(['date_start', 'date_end']);
            
            // Restore original date column and index
            $table->date('date');
            $table->index(['country_id', 'date']);
        });
    }
};
