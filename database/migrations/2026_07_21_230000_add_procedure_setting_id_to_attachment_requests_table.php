<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attachment_requests', function (Blueprint $table) {
            $table->uuid('procedure_setting_id')->nullable()->after('project_id')->index();

            $table->foreign('procedure_setting_id')
                ->references('id')
                ->on('procedure_settings')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attachment_requests', function (Blueprint $table) {
            $table->dropForeign(['procedure_setting_id']);
            $table->dropIndex(['procedure_setting_id']);
            $table->dropColumn('procedure_setting_id');
        });
    }
};
