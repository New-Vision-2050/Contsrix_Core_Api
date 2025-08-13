<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->string('currency');

            // company access program
            $table->uuid('company_access_program_id');
            $table->foreign('company_access_program_id')->references('id')->on('company_access_programs')->onDelete('cascade');

            // Subscription period
            $table->unsignedInteger('subscription_period');
            $table->string('subscription_period_unit');

            // Trial period
            $table->unsignedInteger('trial_period')->nullable();
            $table->string('trial_period_unit')->nullable();

            $table->boolean('is_active')->default(true);

            $table->unique(['company_access_program_id', 'name'], 'cap_package_name_unique');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('packages');
    }
};
