<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class 2025_11_24_141700_create_website_projects_table Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('website_projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('website_project_setting_id');
            $table->uuid('company_id');
            $table->integer('status')->default(1);
            $table->timestamps();

            $table->foreign('website_project_setting_id')
                ->references('id')
                ->on('website_project_settings')
                ->onDelete('cascade');

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('website_projects');
    }
};
