<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        // Optional: Drop if exists (only use in development!)
        Schema::dropIfExists('time_zones');

        Schema::create('time_zones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('country_id')->index();
            $table->string('zone_name');
            $table->integer('gmt_offset');
            $table->string('gmt_offset_name');
            $table->string('abbreviation');
            $table->string('tz_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_zones');
    }
};
