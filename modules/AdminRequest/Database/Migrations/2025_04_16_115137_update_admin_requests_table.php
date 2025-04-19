<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('admin_requests', function (Blueprint $table) {
            $table->uuid("company_id")->nullable()->index();

        });
    }
};
