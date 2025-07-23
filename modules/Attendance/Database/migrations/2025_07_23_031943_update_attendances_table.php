<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Make clock_in_time nullable to accommodate 'waiting' status
            $table->timestamp('start_time')->nullable()->after('clock_out_time');
            $table->timestamp('end_time')->nullable()->after('start_time');
        });
    }
};
