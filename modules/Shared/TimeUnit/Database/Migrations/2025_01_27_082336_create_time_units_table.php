<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('time_units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('time_units');
    }
};
