<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::table('users')->update(['face_verification_exempt' => 1]);
    }

    public function down()
    {
        DB::table('users')->update(['face_verification_exempt' => 0]);
    }
};
