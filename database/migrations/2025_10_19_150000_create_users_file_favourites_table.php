<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users_file_favourites', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->uuid('file_id');
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('file_id')
                ->references('id')
                ->on('files')
                ->onDelete('cascade');

            // Composite primary key to prevent duplicates
            $table->primary(['user_id', 'file_id']);

            // Index for faster queries
            $table->index('user_id');
            $table->index('file_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_file_favourites');
    }
};
