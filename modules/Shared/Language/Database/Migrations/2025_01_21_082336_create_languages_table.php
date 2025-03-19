<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id()->primary();
            $table->string('lang');
            $table->string('lang_ar');
            $table->string('native');
            $table->string('iso_code')->nullable();
            $table->tinyInteger('is_active');
            $table->tinyInteger('is_rtl');
            $table->tinyInteger('is_default');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
