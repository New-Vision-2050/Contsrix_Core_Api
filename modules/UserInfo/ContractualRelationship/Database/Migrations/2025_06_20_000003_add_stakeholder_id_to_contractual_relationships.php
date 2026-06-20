<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contractual_relationships', function (Blueprint $table) {
            $table->uuid('stakeholder_id')->nullable()->index()->after('registration_number');
        });
    }

    public function down(): void
    {
        Schema::table('contractual_relationships', function (Blueprint $table) {
            $table->dropColumn('stakeholder_id');
        });
    }
};
