<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_educational_courses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('global_id')->index();
            $table->string('company_name')->nullable();
            $table->string('authority')->nullable();
            $table->string('name')->nullable();
            $table->string('institute')->nullable();
            $table->string('certificate')->nullable();
            $table->date('date_obtain')->nullable();
            $table->date('date_end')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('user_educational_courses');
    }
};
