<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('module_id')->nullable(); // parent module
            $table->json('name');
            $table->string('slug')->unique();
            $table->timestamps();

            $table->foreign('module_id')
                  ->references('id')
                  ->on('modules')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('modules');
    }
};
