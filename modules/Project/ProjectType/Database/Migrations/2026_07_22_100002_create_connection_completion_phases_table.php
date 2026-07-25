<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connection_completion_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_permit_department_id')->constrained('order_permit_department')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connection_completion_phases');
    }
};
