<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('package_module', function (Blueprint $table) {
            $table->uuid('package_id');
            $table->uuid('module_id');

            $table->foreign('package_id')->references('id')->on('packages')->onDelete('cascade');
            $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');

            $table->primary(['package_id', 'module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_module');
    }
};
