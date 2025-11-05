<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
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
