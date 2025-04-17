<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('job_offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('global_id')->index();

            $table->string('job_offer_number');
            $table->date('date_send');
            $table->date('date_accept');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('job_offers');
    }
};
