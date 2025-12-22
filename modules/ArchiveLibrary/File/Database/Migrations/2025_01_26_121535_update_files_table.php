<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_01_26_121535_update_files_table Migration
{
    public function up()
    {
        Schema::table('files', function (Blueprint $table) {
            $table->uuid('management_hierarchy_id')->nullable()->index();

        });
    }
};
