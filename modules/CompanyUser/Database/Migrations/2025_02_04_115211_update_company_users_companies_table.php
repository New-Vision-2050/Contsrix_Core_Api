<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Modules\User\Models\User;

return new class extends Migration
{
    public function up()
    {
        Schema::create('company_users_companies', function (Blueprint $table) { //pivot table for user and company
            $table->uuid('id')->primary();
            $table->foreignIdFor(\Modules\Company\CompanyCore\Models\Company::class,"company_id");
            $table->foreignIdFor(User::class,"global_company_user_id");
            $table->enum("role",\Modules\CompanyUser\Enum\CompanyUserRole::values());
            $table->enum("status",\Modules\CompanyUser\Enum\CompanyUserStatus::values());
            $table->timestamps();
        });
    }
};
