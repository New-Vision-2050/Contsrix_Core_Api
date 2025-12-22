<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_18_000000_add_status_to_website_services_table Migration
{
    public function up(): void
    {
        Schema::table('website_services', function (Blueprint $table) {
            $table->tinyInteger('status')->default(1)->after('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('website_services', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
