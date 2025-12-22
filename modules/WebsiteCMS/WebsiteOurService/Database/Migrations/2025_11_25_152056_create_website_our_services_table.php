<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\WebsiteCMS\WebsiteOurService\Enums\ServiceTypeEnum;

return new class 2025_11_25_152056_create_website_our_services_table Migration
{
    public function up()
    {
        // Main table
        Schema::create('website_our_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description');
            $table->uuid('company_id')->index();
            $table->integer('status')->default(1);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        // Departments table (has many relationship with website_our_services)
        Schema::create('website_our_service_departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('website_our_service_id')->index();

            $table->string('type'); // cards or hexa
            $table->timestamps();

            $table->foreign('website_our_service_id', 'wosd_wos_fk')
                ->references('id')->on('website_our_services')->onDelete('cascade');
        });

        // Pivot table for department and website_service relationship
        Schema::create('website_our_service_department_website_service', function (Blueprint $table) {
            $table->uuid('website_our_service_department_id');
            $table->uuid('website_service_id');
            $table->timestamps();

            $table->foreign('website_our_service_department_id', 'wosd_ws_dept_fk')
                ->references('id')->on('website_our_service_departments')->onDelete('cascade');
            $table->foreign('website_service_id', 'wosd_ws_service_fk')
                ->references('id')->on('website_services')->onDelete('cascade');

        });
    }

    public function down()
    {
        Schema::dropIfExists('website_our_service_department_website_service');
        Schema::dropIfExists('website_our_service_departments');
        Schema::dropIfExists('website_our_services');
    }
};
