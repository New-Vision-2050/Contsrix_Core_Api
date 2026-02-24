<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('term_setting_term_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('term_setting_id');
            $table->unsignedBigInteger('term_services_id');
            $table->timestamps();

            $table->foreign('term_setting_id')
                ->references('id')
                ->on('term_settings')
                ->onDelete('cascade');

            $table->foreign('term_services_id')
                ->references('id')
                ->on('term_services')
                ->onDelete('cascade');

            $table->unique(['term_setting_id', 'term_services_id'], 'ts_tsrv_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('term_setting_term_services');
    }
};
