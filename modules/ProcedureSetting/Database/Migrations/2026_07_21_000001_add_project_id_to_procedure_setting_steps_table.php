<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PS_STEPS_PROJECT_FK = 'ps_steps_project_fk';

    public function up(): void
    {
        Schema::table('procedure_setting_steps', function (Blueprint $table): void {
            $table->uuid('project_id')->nullable()->after('company_id')->index();
            $table->foreign('project_id', self::PS_STEPS_PROJECT_FK)
                ->references('id')
                ->on('projects')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('procedure_setting_steps', function (Blueprint $table): void {
            $table->dropForeign(self::PS_STEPS_PROJECT_FK);
            $table->dropColumn('project_id');
        });
    }
};
