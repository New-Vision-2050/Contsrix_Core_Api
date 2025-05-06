<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sub_entities', function (Blueprint $table) {
            $table->uuid('id')->primary();

             // Belongs to any model (User, Company, etc.)
             /**
              * @see modules/SubEntity/Resources/config/config.php:available_super_entities
              */
             $table->string('super_entity');

             $table->string('name');

             $table->string('icon', 50);

             // Program relation (required, UUID)
             $table->uuid('main_program_id');
             $table->foreign('main_program_id')->references('id')->on('programs')->cascadeOnDelete();

             $table->boolean('is_active')->default(true);
             $table->boolean('is_registrable')->default(false);

             // Attributes
             $table->json('default_attributes');
             $table->json('optional_attributes')->nullable();

             $table->timestamps();

             // Unique constraint on name within super entity scope
             $table->unique(['name', 'super_entity'], 'unique_name_per_super_entity');
        });
    }

    public function down()
    {
        Schema::table('sub_entities', function (Blueprint $table) {
            $table->dropForeign(['main_program_id']);
        });

        Schema::dropIfExists('sub_entities');
    }
};
