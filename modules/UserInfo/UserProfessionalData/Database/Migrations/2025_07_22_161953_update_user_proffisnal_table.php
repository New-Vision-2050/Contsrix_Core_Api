<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::table('user_professional_datas', function (Blueprint $table) {
            $table->uuid('attendance_constraint_id')->nullable()->index();
        });
    }
};
