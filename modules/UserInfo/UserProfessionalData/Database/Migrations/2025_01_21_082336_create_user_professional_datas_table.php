<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_professional_datas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('global_id')->index();
            $table->uuid('branch_id')->index();
            $table->uuid('management_id')->index();
            $table->uuid('department_id')->index();
            $table->uuid('job_type_id')->index();
            $table->uuid('job_title_id')->index();
            $table->string('job_code')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('user_professional_datas');
    }
};
