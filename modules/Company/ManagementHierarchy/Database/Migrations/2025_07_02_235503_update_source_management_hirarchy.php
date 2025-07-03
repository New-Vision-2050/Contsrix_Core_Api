<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('source_management_hierarchies', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable();
        });
    }
};
