<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('professional_certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('global_id')->index();

            $table->uuid('professional_bodie_id')->index();
            $table->string('accreditation_name')->nullable();
            $table->string('accreditation_number')->nullable();
            $table->string('accreditation_degree')->nullable();
            $table->date('date_obtain');
            $table->date('date_end');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('professional_certificates');
    }
};
