<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::table('user_professional_datas', function (Blueprint $table) {
            $table->uuid('job_type_id')->nullable()->change();
            $table->uuid('job_title_id')->nullable()->change();
        });
    }
};
