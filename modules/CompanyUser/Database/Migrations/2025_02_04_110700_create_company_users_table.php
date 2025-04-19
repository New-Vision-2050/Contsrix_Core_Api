<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('company_users', function (Blueprint $table) {
            $table->uuid('id')->primary()->index();
            $table->uuid('global_id')->nullable()->index();
            $table->string('name');
            $table->string('email')->unique()->index();
            $table->string('phone')->unique()->index();
            $table->string('phone_code')->index();
            $table->string('residence')->unique()->index()->nullable();
            $table->string('identity')->unique()->index()->nullable();
            $table->string('passport')->unique()->index()->nullable();
            $table->string('border_number')->unique()->index()->nullable();



            $table->foreignIdFor(\Modules\Country\Models\Country::class,'country_id')->index();

            $table->timestamps();
        });
    }
};
