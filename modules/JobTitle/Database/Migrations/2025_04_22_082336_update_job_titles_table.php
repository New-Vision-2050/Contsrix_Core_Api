<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('job_titles', function (Blueprint $table) {
            $table->string('type')->nullable();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('type');
    }
};
