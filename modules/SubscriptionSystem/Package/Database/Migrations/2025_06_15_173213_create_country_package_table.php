<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('country_package', function (Blueprint $table) {
            $table->uuid('package_id');
            $table->uuid('country_id');
            $table->timestamps();
        
            // Composite primary key
            $table->primary(['package_id', 'country_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('country_package');
    }
};
