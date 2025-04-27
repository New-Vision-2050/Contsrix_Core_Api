<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_salaries', function (Blueprint $table) {
            $table->renameColumn('basic', 'hour_rate');
        });
    }

    public function down(): void
    {
        Schema::table('user_salaries', function (Blueprint $table) {
            $table->renameColumn('hour_rate', 'basic');
        });
    }
};

