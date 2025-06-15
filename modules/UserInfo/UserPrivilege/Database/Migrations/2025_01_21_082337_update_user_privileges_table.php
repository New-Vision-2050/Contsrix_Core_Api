<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_privileges', function (Blueprint $table) {
            $table->string('privilege_id')->index();
        });
    }

};
