<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_procedure_takens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('processable_type');
            $table->uuid('processable_id');
            $table->uuid('procedure_setting_id');
            $table->string('form')->nullable();
            $table->uuid('taken_by')->nullable();
            $table->timestamp('taken_at')->useCurrent();

            $table->timestamps();

            $table->index(['processable_type', 'processable_id']);
            $table->unique(
                ['processable_type', 'processable_id', 'procedure_setting_id'],
                'ipt_unique_processable_procedure',
            );

            $table->foreign('procedure_setting_id')
                ->references('id')
                ->on('procedure_settings')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_procedure_takens');
    }
};
