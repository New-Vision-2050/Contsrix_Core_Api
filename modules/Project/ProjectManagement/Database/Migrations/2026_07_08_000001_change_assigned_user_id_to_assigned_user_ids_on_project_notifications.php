<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_notifications', function (Blueprint $table) {
            $table->json('assigned_user_ids')->nullable()->after('assigned_user_id');
            $table->boolean('all_users_can_approve')->default(false)->after('assigned_user_ids');
            $table->boolean('independent_progress')->default(true)->after('all_users_can_approve');
        });

        // Migrate existing single assigned_user_id values into the JSON array.
        DB::statement('
            UPDATE project_notifications
            SET assigned_user_ids = CASE
                WHEN assigned_user_id IS NOT NULL
                THEN JSON_ARRAY(assigned_user_id)
                ELSE NULL
            END
        ');

        Schema::table('project_notifications', function (Blueprint $table) {
            $table->dropForeign('project_notifications_assigned_user_id_foreign');
            $table->dropIndex('pn_assigned_user_idx');
            $table->dropColumn('assigned_user_id');
        });

        Schema::table('project_notifications', function (Blueprint $table) {
            $table->index('company_id', 'pn_company_idx');
        });
    }

    public function down(): void
    {
        Schema::table('project_notifications', function (Blueprint $table) {
            $table->dropIndex('pn_company_idx');
        });

        Schema::table('project_notifications', function (Blueprint $table) {
            $table->uuid('assigned_user_id')->nullable()->after('permit_recipient');
            $table->index('assigned_user_id', 'pn_assigned_user_idx');
            $table->foreign('assigned_user_id')->references('id')->on('users')->nullOnDelete();
        });

        // Restore single assigned_user_id from the first element of the JSON array.
        DB::statement('
            UPDATE project_notifications
            SET assigned_user_id = JSON_UNQUOTE(JSON_EXTRACT(assigned_user_ids, "$[0]"))
            WHERE assigned_user_ids IS NOT NULL
        ');

        Schema::table('project_notifications', function (Blueprint $table) {
            $table->dropColumn(['assigned_user_ids', 'all_users_can_approve', 'independent_progress']);
        });
    }
};
