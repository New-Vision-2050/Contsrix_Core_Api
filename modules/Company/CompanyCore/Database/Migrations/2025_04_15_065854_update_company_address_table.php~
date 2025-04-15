<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('company_address', function (Blueprint $table) {
            $table->uuid("management_hierarchy_id")->index()->nullable();
            $table->boolean("is_first_branch")->index()->default(0);
        });
    }
};
