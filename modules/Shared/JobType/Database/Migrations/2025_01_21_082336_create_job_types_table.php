<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('job_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('company_id')->index();
            $table->string('type')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('job_types');
    }
};
