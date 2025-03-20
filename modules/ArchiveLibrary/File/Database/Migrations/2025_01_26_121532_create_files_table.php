<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('Folder_id')->index()->nullable();
            $table->enum('access_type', ['public', 'private'])->default('private'); 
            $table->timestamps();
        });
    }
};
