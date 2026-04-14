<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_holiday_days', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('public_holiday_id')->index();
            $table->date('date');
            $table->boolean('is_compensation')->default(false);
            $table->timestamps();

            $table->foreign('public_holiday_id')
                ->references('id')
                ->on('public_holidays')
                ->cascadeOnDelete();

            $table->unique(['public_holiday_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_holiday_days');
    }
};
