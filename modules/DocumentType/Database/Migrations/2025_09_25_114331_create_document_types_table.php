<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_09_25_114331_create_document_types_table Migration
{
    public function up()
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_types');
    }
};
