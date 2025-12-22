<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_10_09_105407_create_files_share_table Migration
{
    public function up()
    {
        Schema::create('file_shares', function (Blueprint $table) {
            $table->string('user_id');
            $table->uuid("file_id");

            $table->timestamps();
        });
    }
};
