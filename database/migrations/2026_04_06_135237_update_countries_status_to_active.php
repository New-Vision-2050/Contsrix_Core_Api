<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::table('countries')
            ->where('status', 0)
            ->update(['status' => 1]);
    }

    public function down()
    {
        DB::table('countries')
            ->where('status', 1)
            ->update(['status' => 0]);
    }
};
