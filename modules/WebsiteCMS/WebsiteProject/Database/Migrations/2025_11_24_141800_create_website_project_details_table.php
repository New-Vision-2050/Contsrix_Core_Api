<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('website_project_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('website_project_id');
            $table->uuid('website_service_id');
            $table->timestamps();

            $table->foreign('website_project_id')
                ->references('id')
                ->on('website_projects')
                ->onDelete('cascade');

            $table->foreign('website_service_id')
                ->references('id')
                ->on('website_services')
                ->onDelete('cascade');

            $table->unique(['website_project_id', 'website_service_id'], 'wp_ws_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('website_project_details');
    }
};
