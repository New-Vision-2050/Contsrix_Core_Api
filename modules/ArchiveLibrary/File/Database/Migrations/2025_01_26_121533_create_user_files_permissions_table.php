<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_file_permissions', function (Blueprint $table) {
            $table->string('user_id');
            $table->string('file_id');
            $table->string('folder_id'); // Add folder_id column
            $table->enum('permission_type', ['view', 'edit', 'delete']);
            $table->timestamps();

            // Use user_id, file_id, and folder_id as the primary key
            $table->primary(['user_id', 'file_id', 'folder_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_file_permissions');
    }
};
