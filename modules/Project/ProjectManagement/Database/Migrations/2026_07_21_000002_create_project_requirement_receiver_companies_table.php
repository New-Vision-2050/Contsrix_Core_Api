<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_requirement_receiver_companies', function (Blueprint $table): void {
            $table->uuid('project_requirement_id');
            $table->uuid('company_id');
            $table->timestamps();

            $table->foreign('project_requirement_id', 'project_req_receiver_req_fk')
                ->references('id')
                ->on('project_requirements')
                ->cascadeOnDelete();

            $table->foreign('company_id', 'project_req_receiver_company_fk')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            $table->primary(
                ['project_requirement_id', 'company_id'],
                'project_req_receiver_companies_primary'
            );
            $table->index('company_id', 'project_req_receiver_companies_company_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_requirement_receiver_companies');
    }
};
