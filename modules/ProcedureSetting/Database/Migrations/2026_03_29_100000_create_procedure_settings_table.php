<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procedure_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->enum('type', ['client_request', 'price_offer', 'contract']);
            $table->enum('execute_type', ['parallel', 'sequence']);
            $table->string('icon')->nullable();
            $table->decimal('percentage', 5, 2)->default(0);
            $table->uuid('company_id')->nullable()->index();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedure_settings');
    }
};
