<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_folder_permissions', function (Blueprint $table) {
            $table->string('user_id');
            $table->string('folder_id');
            $table->enum('permission_type', ['view', 'edit', 'delete']);
            $table->timestamps();

            $table->primary(['user_id', 'folder_id']);
        });
    }
    public function down()
    {
        Schema::dropIfExists('user_folder_permissions');
    }
};
