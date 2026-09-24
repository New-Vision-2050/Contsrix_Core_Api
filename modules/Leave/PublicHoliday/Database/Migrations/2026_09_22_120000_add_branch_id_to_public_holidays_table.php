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
            // Preserve imported country holidays while allowing branch-specific holidays.
            $table->unsignedBigInteger('country_id')->nullable()->change();
            $table->foreignId('branch_id')->nullable()
                ->constrained('management_hierarchies')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('public_holidays', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
