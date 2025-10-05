<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('files', function (Blueprint $table) {
            $table->uuid('reference_number')->nullable()->index();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
        });
    }
};
