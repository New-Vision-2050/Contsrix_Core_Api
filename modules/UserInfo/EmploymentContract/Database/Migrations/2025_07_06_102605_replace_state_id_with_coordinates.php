<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('employment_contracts', function (Blueprint $table) {
            // Drop state_id column
            $table->dropColumn('state_id');
            
            // Add latitude and longitude columns
            $table->decimal('latitude', 10, 8)->nullable()->after('country_id');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });
    }

    public function down()
    {
        Schema::table('employment_contracts', function (Blueprint $table) {
            // Remove latitude and longitude columns
            $table->dropColumn(['latitude', 'longitude']);
            
            // Restore state_id column
            $table->unsignedBigInteger('state_id')->nullable()->index();
        });
    }
};
