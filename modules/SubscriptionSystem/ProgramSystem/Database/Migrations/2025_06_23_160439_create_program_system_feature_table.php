<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('program_system_feature', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('program_system_id')->constrained('program_systems')->onDelete('cascade');
            $table->foreignUuid('feature_id')->constrained('features')->onDelete('cascade');
            $table->foreignUuid('module_id')->constrained('modules')->onDelete('cascade');
            $table->timestamps();
        });
    }
};
