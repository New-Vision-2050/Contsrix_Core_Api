<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('website_terms_and_conditions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');

            $table->longText("content");

            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('founders');
    }
};
