<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
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
