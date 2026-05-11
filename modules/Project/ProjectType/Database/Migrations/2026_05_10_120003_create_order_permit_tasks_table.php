<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_permit_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_type_id');
            $table->string('code', 255)->nullable();
            $table->string('name', 255)->nullable();
            $table->timestamps();

            $table->foreign('project_type_id')
                ->references('id')
                ->on('project_types')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_permit_tasks');
    }
};
