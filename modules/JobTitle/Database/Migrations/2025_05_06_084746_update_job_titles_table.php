<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('job_titles', function (Blueprint $table) {
            $table->uuid("job_type_id")->nullable();
            $table->boolean("status")->default(true);
            $table->text("description")->nullable();
            $table->uuid("company_id")->nullable();
        });
    }
};
