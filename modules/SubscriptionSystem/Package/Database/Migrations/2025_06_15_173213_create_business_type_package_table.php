<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('business_type_package', function (Blueprint $table) {
            $table->uuid('package_id');
            $table->uuid('business_type_id');
            $table->timestamps();
        
            // Composite primary key
            $table->primary(['package_id', 'business_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_type_package');
    }
};
