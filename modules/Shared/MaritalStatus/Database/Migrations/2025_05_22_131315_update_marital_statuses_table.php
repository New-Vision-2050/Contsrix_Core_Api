<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('marital_statuses', function (Blueprint $table) {
            $table->string('type')->nullable();
        });
    }


};
