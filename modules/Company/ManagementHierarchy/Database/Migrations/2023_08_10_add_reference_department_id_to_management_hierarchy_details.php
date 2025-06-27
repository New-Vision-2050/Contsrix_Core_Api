<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReferenceDepartmentIdToManagementHierarchyDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('management_hierarchy_details', function (Blueprint $table) {
            $table->string('reference_department_id')->nullable()->after('reference_user_id')
                ->comment('ID of the original department this department was cloned from');
            
            // Add index for faster lookups
            $table->index('reference_department_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('management_hierarchy_details', function (Blueprint $table) {
            $table->dropIndex(['reference_department_id']);
            $table->dropColumn('reference_department_id');
        });
    }
}
