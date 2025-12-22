<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_10_28_220000_change_country_id_to_string_in_store_branches_table Migration
{
    public function up(): void
    {
        Schema::table('store_branches', function (Blueprint $table) {
            $table->string('country_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('store_branches', function (Blueprint $table) {
            $table->uuid('country_id')->nullable()->change();
        });
    }
};
