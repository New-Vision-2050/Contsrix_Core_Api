<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;


return new class extends Migration {
    public function up(): void
    {
        Schema::table('company_official_documents', function (Blueprint $table) {
            $table->text('description')->nullable()->change();

        });
    }

    public function down(): void
    {
    }
};
