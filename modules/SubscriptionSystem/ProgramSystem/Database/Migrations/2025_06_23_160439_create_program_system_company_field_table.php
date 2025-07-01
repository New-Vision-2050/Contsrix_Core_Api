<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('program_system_company_field', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('program_system_id')->constrained('program_systems')->onDelete('cascade');
            $table->foreignUuid('company_field_id')->constrained('company_fields')->onDelete('cascade');
            $table->timestamps();
        });

    }
};
