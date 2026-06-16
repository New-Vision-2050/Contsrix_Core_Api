<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_task_items')) {
            return;
        }

        Schema::create('employee_task_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('model_class');
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('employee_task_items');
    }
};
