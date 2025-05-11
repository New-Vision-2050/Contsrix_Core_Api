<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('time_units', function (Blueprint $table) {
            $table->string('code');
        });

    }
    public function down(): void
    {
        Schema::dropIfExists('code');
    }
};
