<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_insurances', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('policy_number');
            $table->date('start_date')->nullable()->after('end_date');
            $table->decimal('value', 15, 2)->nullable()->after('start_date');
            $table->unsignedInteger('individuals_count')->nullable()->after('value');
            $table->uuid('employee_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('medical_insurances', function (Blueprint $table) {
            $table->dropColumn(['provider', 'start_date', 'value', 'individuals_count']);
            $table->uuid('employee_id')->nullable(false)->change();
        });
    }
};
