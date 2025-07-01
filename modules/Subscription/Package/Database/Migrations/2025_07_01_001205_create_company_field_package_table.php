<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('company_field_package', function (Blueprint $table) {
            $table->uuid('package_id');
            $table->uuid('company_field_id');

            $table->foreign('package_id')
                ->references('id')
                ->on('packages')
                ->onDelete('cascade');

            $table->foreign('company_field_id')
                ->references('id')
                ->on('company_fields')
                ->onDelete('cascade');

            $table->primary(['package_id', 'company_field_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_field_package');
    }
};
