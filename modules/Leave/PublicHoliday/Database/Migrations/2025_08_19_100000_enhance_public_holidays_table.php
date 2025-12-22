<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_08_19_100000_enhance_public_holidays_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('public_holidays', function (Blueprint $table) {
            // Add new fields for enhanced holiday management
            $table->string('country_code', 2)->nullable()->after('country_id')->index();
            $table->string('name_ar')->nullable()->after('name'); // Arabic name
            $table->year('year')->nullable()->after('date_end')->index();
            $table->enum('holiday_type', ['national', 'local', 'religious', 'observance', 'other'])
                  ->default('national')->after('year')->index();
            $table->boolean('is_recurring')->default(true)->after('holiday_type');
            $table->text('description')->nullable()->after('is_recurring');
            $table->string('external_api_id')->nullable()->after('description')->index();
            $table->json('api_data')->nullable()->after('external_api_id');
            $table->json('tags')->nullable()->after('api_data');
            $table->boolean('is_active')->default(true)->after('tags')->index();

            // Add composite indexes for better performance
            $table->index(['country_id', 'year', 'is_active']);
            $table->index(['country_code', 'year', 'is_active']);
            $table->index(['holiday_type', 'is_active']);
            $table->index(['date_start', 'date_end', 'is_active']);
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
