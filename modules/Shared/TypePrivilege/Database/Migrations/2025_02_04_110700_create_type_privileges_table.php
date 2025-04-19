<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('type_privileges', function (Blueprint $table) {
            $table->uuid('id')->primary()->index();
            $table->timestamps();
        });
    }
};
