<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * this migration present branches for all roles for user
     * @return void
     */
    public function up()
    {
        Schema::create('company_users_company_management_hierarchies', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Using custom shorter constraint names
            $table->foreignIdFor(\Modules\Company\ManagementHierarchy\Models\ManagementHierarchy::class, 'management_hierarchy_id')
                  ->constrained('management_hierarchies')
                  ->onDelete("cascade")
                  ->name('cucmh_mh_foreign');

            $table->foreignIdFor(\Modules\CompanyUser\Models\CompanyUserCompany::class, "company_user_company_id")
                  ->constrained('company_user_companies')
                  ->onDelete("cascade")
                  ->name('cucmh_cuc_foreign');

            $table->foreignIdFor(\Modules\User\Models\User::class, "user_id")
                  ->constrained()
                  ->onDelete("cascade")
                  ->name('cucmh_user_foreign'); // this for filter very simple to company

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_users_company_management_hierarchies');
    }
};
