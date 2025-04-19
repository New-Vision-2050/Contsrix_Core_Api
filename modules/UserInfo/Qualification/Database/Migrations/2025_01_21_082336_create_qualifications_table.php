<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('qualifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('global_id')->index();

            $table->uuid('country_id')->index();
            $table->uuid('university_id')->index();
            $table->uuid('academic_qualification_id')->index();
            $table->uuid('academic_specialization_id')->index();
            $table->integer('study_rate');
            $table->date('graduation_date');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('qualifications');
    }
};
