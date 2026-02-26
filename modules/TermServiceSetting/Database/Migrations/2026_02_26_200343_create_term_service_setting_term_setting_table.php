<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('term_service_setting_term_setting', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('term_service_setting_id');
            $table->unsignedBigInteger('term_setting_id');
            $table->timestamps();

            $table->index('term_service_setting_id', 'tss_ts_service_idx');
            $table->index('term_setting_id', 'tss_ts_setting_idx');
            $table->unique(['term_service_setting_id', 'term_setting_id'], 'tss_ts_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('term_service_setting_term_setting');
    }
};
