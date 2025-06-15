<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_legal_data', function (Blueprint $table) {
            $table->uuid('registration_type_id')->nullable()->change();
            $table->string('registration_number')->nullable()->change();
            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('company_legal_data', function (Blueprint $table) {
            $table->uuid('registration_type_id')->nullable(false)->change();
            $table->string('registration_number')->nullable(false)->change();
            $table->date('start_date')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
        });
    }
};
