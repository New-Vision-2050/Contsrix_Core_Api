<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('client_details', function (Blueprint $table) {
            // Make the existing company_id column nullable if it isn't already
            $table->uuid("company_id")->nullable();
            $table->foreign("company_id")->references("id")->on("companies");

        });
    }
};
