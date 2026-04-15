<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('client_requests', function (Blueprint $table) {
            $table->uuid('created_by_user_id')->nullable()->after('company_id');
        });
    }

    public function down()
    {
        Schema::table('client_requests', function (Blueprint $table) {
            $table->dropColumn('created_by_user_id');
        });
    }
};
