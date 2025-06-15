<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->json('name');
            $table->string('slug');
            $table->boolean('is_active')->default(true);
<<<<<<< HEAD
=======
            
>>>>>>> 7be6c72c (merge with stage (first version ))
            $table->uuid('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('programs')->onDelete('cascade');
            $table->unique(['parent_id', 'slug']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('programs');
    }
};
