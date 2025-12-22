<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_18_000001_make_reference_number_nullable_in_website_services_table Migration
{
    public function up(): void
    {
        Schema::table('website_services', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('website_services', function (Blueprint $table) {
            $table->string('reference_number')->nullable(false)->change();
        });
    }
};
