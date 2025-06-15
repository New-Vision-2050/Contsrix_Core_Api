<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_privileges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('global_id')->index();

            $table->string('type_privilege')->nullable();
            $table->string('type_allowance')->nullable();
            $table->string('rate')->nullable();
            $table->string('description')->nullable();

            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('user_privileges');
    }
};
