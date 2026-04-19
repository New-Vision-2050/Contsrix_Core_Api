<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_share_types', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('level'); // 'type', 'relation', 'role'
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_share_types');
    }
};
