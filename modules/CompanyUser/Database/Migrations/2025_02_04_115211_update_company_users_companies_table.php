<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Modules\User\Models\User;

return new class 2025_02_04_115211_update_company_users_companies_table Migration
{
    public function up()
    {
        Schema::table('company_users_companies', function (Blueprint $table) { //pivot table for user and company
                $table->uuid("sub_entity_id")->nullable();
        });
    }
};
