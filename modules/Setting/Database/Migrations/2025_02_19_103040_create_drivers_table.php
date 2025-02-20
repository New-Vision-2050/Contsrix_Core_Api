<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string("driver_type");
            $table->string("name")->unique();//like sms mora , web mail
            $table->json("config");
            $table->foreignIdFor(Modules\Company\CompanyCore\Models\Company::class,"company_id")->index();
            $table->timestamps();
        });
    }
};
