<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('program_system_business_types')) {

            Schema::create('program_system_business_types', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('program_system_id')->constrained('program_systems')->onDelete('cascade');
                $table->foreignUuid('business_type_id')->constrained('business_types')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }
};
