<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_10_28_203300_drop_setting_page_id_from_features_table Migration
{
    public function up(): void
    {
        Schema::table('features', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['setting_page_id']);
            
            // Drop setting_page_id column
            $table->dropColumn('setting_page_id');
            $table->string('type', 50)->default('home');
        });
    }
    
    public function down(): void
    {
        Schema::table('features', function (Blueprint $table) {
            $table->dropColumn('type');
            // Add setting_page_id back
            $table->uuid('setting_page_id')->nullable()->index()->after('company_id');
            
            // Add foreign key constraint back
            $table->foreign('setting_page_id')->references('id')->on('setting_pages')->onDelete('set null');
        });
    }
};
