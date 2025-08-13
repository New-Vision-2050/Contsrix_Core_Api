<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Make clock_in_time nullable to accommodate 'waiting' status
            $table->date('date')->nullable()->after('start_time');
            $table->string('day_status')->nullable()->after('start_time');
        });
    }
};
