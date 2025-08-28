<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::table('client_details', function (Blueprint $table) {
            $table->foreignIdFor(Modules\Company\ManagementHierarchy\Models\ManagementHierarchy::class , "original_branch_id")->nullable();
        });
    }
};
