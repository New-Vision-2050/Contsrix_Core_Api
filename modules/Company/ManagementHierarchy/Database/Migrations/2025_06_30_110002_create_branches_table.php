<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('company_id')->index();
            $table->text('path')->nullable();
            $table->string('manager_id')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('phone_code')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->boolean('is_first_branch')->default(0)->index();
            $table->boolean('is_main')->default(0)->index();
            $table->boolean('is_active')->default(1)->index();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE branches ADD INDEX branches_path_index (`path`(191))');
    }

    public function down()
    {
        Schema::dropIfExists('branches');
    }
};
