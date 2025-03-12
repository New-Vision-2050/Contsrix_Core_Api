<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('time_zones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('country_id')->index();
            $table->string('time_zone');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('time_zones');
    }
};
