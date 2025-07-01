<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('program_systems', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
        });
    }
};
