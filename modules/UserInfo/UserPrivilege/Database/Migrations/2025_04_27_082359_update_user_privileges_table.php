<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_privileges', function (Blueprint $table) {
            $table->string("medical_insurance_id")->nullable();
        });
    }

    public function down(): void
    {

    }
};
