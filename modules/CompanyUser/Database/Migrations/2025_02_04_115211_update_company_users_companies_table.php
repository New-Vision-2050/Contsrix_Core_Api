<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Modules\User\Models\User;

return new class extends Migration
{
    public function up()
    {
        Schema::table('company_users_companies', function (Blueprint $table) { //pivot table for user and company
                $table->uuid("sub_entity_id")->nullable();
        });
    }
};
