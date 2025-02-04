<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('company_users_companies', function (Blueprint $table) { //pivot table for user and company
            $table->uuid('id')->primary();
            $table->foreignIdFor(\Modules\Company\Models\Company::class,"company_id")->constrained();
            $table->foreignIdFor(\Modules\CompanyUser\Models\CompanyUser::class,"company_user_id")->constrained();
            $table->enum("role",\Modules\CompanyUser\Enum\CompanyUserRole::values());
            $table->enum("status",\Modules\CompanyUser\Enum\CompanyUserStatus::values());
            $table->timestamps();
        });
    }
};
