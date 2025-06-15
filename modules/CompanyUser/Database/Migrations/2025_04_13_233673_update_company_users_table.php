<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('company_users', function (Blueprint $table) {
            $table->date("Work_permit_start_date")->nullable();
            $table->date("Work_permit_end_date")->nullable();
            $table->string("Work_permit")->nullable();

        });
    }
};
