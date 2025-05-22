<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_relatives', function (Blueprint $table) {
            $table->renameColumn('marital_status', 'marital_status_id');
        });
    }

    public function down()
    {
        Schema::table('user_relatives', function (Blueprint $table) {
            $table->renameColumn('marital_status_id', 'marital_status');
        });
    }
};

