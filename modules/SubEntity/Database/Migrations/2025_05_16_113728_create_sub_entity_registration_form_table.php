<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('sub_entity_registration_form', function (Blueprint $table) {
            $table->uuid('sub_entity_id');
            $table->uuid('registration_form_id');

            $table->foreign('sub_entity_id')->references('id')->on('sub_entities')->cascadeOnDelete();
            $table->foreign('registration_form_id')->references('id')->on('registration_forms')->cascadeOnDelete();

            $table->primary(['sub_entity_id', 'registration_form_id']);
        });
    }
};
