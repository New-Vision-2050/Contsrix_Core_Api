<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_official_documents', function (Blueprint $table) {
            $table->uuid('start_date')->nullable()->change();
            $table->uuid('end_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('company_official_documents', function (Blueprint $table) {
            $table->uuid('start_date')->nullable(false)->change();
            $table->uuid('end_date')->nullable(false)->change();

        });
    }
};
