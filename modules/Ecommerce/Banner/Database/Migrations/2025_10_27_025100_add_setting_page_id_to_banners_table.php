<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            // Add setting_page_id relationship
            $table->uuid('setting_page_id')->nullable()->index()->after('company_id');
            
            // Foreign key constraint
            $table->foreign('setting_page_id')->references('id')->on('setting_pages')->onDelete('set null');
        });
    }
    
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropForeign(['setting_page_id']);
            $table->dropColumn('setting_page_id');
        });
    }
};
