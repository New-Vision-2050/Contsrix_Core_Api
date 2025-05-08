<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('salary_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('salary_types');
    }
};
