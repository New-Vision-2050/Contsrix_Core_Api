<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_holidays', function (Blueprint $table) {
            $table->uuid('recurrence_source_id')->nullable();
            $table->unique(['recurrence_source_id', 'year'], 'public_holiday_recurrence_year_unique');
        });
    }

    public function down(): void
    {
        Schema::table('public_holidays', function (Blueprint $table) {
            $table->dropUnique('public_holiday_recurrence_year_unique');
            $table->dropColumn('recurrence_source_id');
        });
    }
};
