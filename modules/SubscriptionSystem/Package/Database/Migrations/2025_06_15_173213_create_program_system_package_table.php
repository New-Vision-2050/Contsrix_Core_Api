<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('program_system_package', function (Blueprint $table) {
            $table->uuid('package_id');
            $table->uuid('program_system_id');
            $table->timestamps();
        
            // Composite primary key
            $table->primary(['package_id', 'program_system_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_systems');
    }
};
