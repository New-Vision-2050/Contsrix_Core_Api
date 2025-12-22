<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_08_19_100001_enhance_public_holidays_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('public_holidays', function (Blueprint $table) {
            // Add new fields for enhanced holiday management
            $table->text('description_ar')->nullable()->after('description'); // Arabic description


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('public_holidays', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['country_id', 'year', 'is_active']);
            $table->dropIndex(['country_code', 'year', 'is_active']);
            $table->dropIndex(['holiday_type', 'is_active']);
            $table->dropIndex(['date_start', 'date_end', 'is_active']);

            // Drop individual indexes
            $table->dropIndex(['country_code']);
            $table->dropIndex(['year']);
            $table->dropIndex(['holiday_type']);
            $table->dropIndex(['external_api_id']);
            $table->dropIndex(['is_active']);

            // Drop columns
            $table->dropColumn([
                'country_code',
                'name_ar',
                'year',
                'holiday_type',
                'is_recurring',
                'description',
                'description_ar',
                'external_api_id',
                'api_data',
                'tags',
                'is_active'
            ]);
        });
    }
};
