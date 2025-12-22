<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_10_28_203200_drop_setting_page_id_add_type_to_banners_table Migration
{
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['setting_page_id']);
            
            // Drop setting_page_id column
            $table->dropColumn('setting_page_id');
            
            // Add type column back
            $table->string('type', 50)->default('home')->after('url');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
        });
    }
    
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            // Drop type column
            $table->dropColumn('type');
            $table->dropColumn('title');
            $table->dropColumn('description');
            
            // Add setting_page_id back
            $table->uuid('setting_page_id')->nullable()->index()->after('company_id');
            
            // Add foreign key constraint back
            $table->foreign('setting_page_id')->references('id')->on('setting_pages')->onDelete('set null');
        });
    }
};
