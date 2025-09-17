<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('source_management_hierarchies', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            
            // Add foreign key constraint to reference the same table
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('source_management_hierarchies')
                  ->onDelete('cascade');
                  
            // Add index for better performance
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('source_management_hierarchies', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
