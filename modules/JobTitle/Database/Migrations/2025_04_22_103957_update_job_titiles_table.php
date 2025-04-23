<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('job_titles', function (Blueprint $table) {

            $table->boolean("for_central_company")->default(0);

        });
    }
};
