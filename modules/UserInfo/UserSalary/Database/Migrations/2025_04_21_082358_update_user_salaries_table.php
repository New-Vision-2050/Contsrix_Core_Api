<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::table('user_salaries')
        ->whereRaw("basic REGEXP '[^0-9.]'")
        ->update(['basic' => '0.00']);

        Schema::table('user_salaries', function (Blueprint $table) {
            $table->decimal('basic', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_salaries', function (Blueprint $table) {
            $table->string('basic')->nullable()->change();
        });
    }
};
