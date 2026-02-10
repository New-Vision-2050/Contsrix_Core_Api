<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('contractual_relationships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('global_id')->index();
            $table->uuid('contractual_relationship_type_id')->index();
            $table->string('employment_name')->nullable();
            $table->string('registration_number')->nullable();
            $table->timestamps();

            $table->foreign('contractual_relationship_type_id', 'cr_type_id_foreign')
                ->references('id')
                ->on('contractual_relationship_types')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contractual_relationships');
    }
};
