<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('managements', function (Blueprint $table) {
            $table->unsignedInteger('users_count')->default(0)->after('is_main')
                ->comment('Count of users directly assigned to this management');
            
            // Add index for performance optimization
            $table->index('users_count');
        });

        // Sync initial users_count from management_hierarchies table
        DB::statement("
            UPDATE managements m 
            INNER JOIN management_hierarchies mh ON m.management_hierarchy_id = mh.id 
            SET m.users_count = mh.users_count
        ");
    }

    public function down()
    {
        Schema::table('managements', function (Blueprint $table) {
            $table->dropIndex(['users_count']);
            $table->dropColumn('users_count');
        });
    }
};
