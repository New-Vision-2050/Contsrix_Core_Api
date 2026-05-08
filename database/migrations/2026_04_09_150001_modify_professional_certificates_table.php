<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('professional_certificates', function (Blueprint $table) {
            // Drop the old column
            $table->dropColumn('accreditation_degree');
            
            // Add the new foreign key
            $table->unsignedBigInteger('professional_degree_id')->nullable()->after('accreditation_number');
            $table->foreign('professional_degree_id')
                ->references('id')
                ->on('professional_degrees')
                ->onDelete('set null');
            
            $table->index('professional_degree_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('professional_certificates', function (Blueprint $table) {
            // Drop foreign key and column
            $table->dropForeign(['professional_degree_id']);
            $table->dropColumn('professional_degree_id');
            
            // Restore the old column
            $table->string('accreditation_degree')->nullable();
        });
    }
};
