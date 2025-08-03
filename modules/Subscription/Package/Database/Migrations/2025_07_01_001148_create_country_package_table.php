<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('country_package', function (Blueprint $table) {
            $table->uuid('package_id');
            $table->unsignedMediumInteger('country_id');

            $table->foreign('package_id')
                ->references('id')
                ->on('packages')
                ->onDelete('cascade');

            $table->foreign('country_id')
                ->references('id')
                ->on('countries')
                ->onDelete('cascade');

            $table->primary(['package_id', 'country_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('country_package');
    }
};
