<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_order_permit_note_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_order_permit_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('note')->nullable();
            $table->string('timezone')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamps();

            $table->foreign('project_order_permit_id')
                ->references('id')
                ->on('project_order_permit')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_order_permit_note_logs');
    }
};
