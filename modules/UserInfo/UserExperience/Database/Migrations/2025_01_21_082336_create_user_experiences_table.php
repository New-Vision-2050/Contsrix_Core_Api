<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_experiences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('global_id')->index();

            $table->string('job_name')->nullable();
            $table->string('training_from')->nullable();
            $table->string('training_to')->nullable();
            $table->string('company_name')->nullable();
            $table->string('about')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('user_experiences');
    }
};
