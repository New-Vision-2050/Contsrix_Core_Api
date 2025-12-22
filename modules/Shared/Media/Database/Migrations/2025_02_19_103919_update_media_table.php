<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_02_19_103919_update_media_table Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->uuid("file_id")->nullable()->after("folder_id");


        });
    }
};
