<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('login_ways', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string("name");
            $table->foreignIdFor(Modules\Company\CompanyCore\Models\Company::class,"company_id")->index();
            $table->tinyInteger("default")->default(0);
            $table->timestamps();
        });
    }
};
