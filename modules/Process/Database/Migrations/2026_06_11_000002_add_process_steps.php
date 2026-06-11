<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PS_STEP_AT_USER_FK = 'process_step_at_user_fk';
    private const PS_STEP_AT_STEP_FK = 'process_step_at_step_fk';

    public function up(): void
    {
        if (! Schema::hasTable('process_steps')) {
            return;
        }
        Schema::table('process_steps', function (Blueprint $table) {
            $table->boolean('notify_by_email')->default(false)->after('status');
                $table->boolean('notify_by_whatsapp')->default(false)->after('notify_by_email');

            $table->boolean('notify_by_sms')->default(false)->after('notify_by_whatsapp');
                $table->unsignedSmallInteger('auto_approval_within_hours')->nullable()->after('notify_by_sms');
                $table->boolean('is_view_only')->default(false)->after('auto_approval_within_hours');
                $table->boolean('is_return_with_notes')->default(false)->after('is_view_only');
                $table->unsignedSmallInteger('approval_within_days')->nullable()->after('is_return_with_notes');
                $table->unsignedSmallInteger('approval_within_hours')->nullable()->after('approval_within_days');

        });
          Schema::create('process_step_action_takers', function (Blueprint $table) {
                $table->id();
                $table->uuid('process_step_id');
                $table->uuid('user_id');
                $table->timestamps();

                $table->foreign('process_step_id', self::PS_STEP_AT_STEP_FK)
                    ->references('id')
                    ->on('process_steps')
                    ->cascadeOnDelete();

                if (Schema::hasTable('users')) {
                    $table->foreign('user_id', self::PS_STEP_AT_USER_FK)
                        ->references('id')
                        ->on('users')
                        ->cascadeOnDelete();
                }



                $table->unique(['process_step_id', 'user_id'], 'process_step_action_takers_step_user_unique');
            });
    }


    public function down(): void
    {
        if (! Schema::hasTable('process_steps')) {
            return;
        }

        Schema::table('process_steps', function (Blueprint $table) {
            $table->dropColumn('notify_by_sms');
            $table->dropColumn('auto_approval_within_hours');
            $table->dropColumn('is_view_only');
            $table->dropColumn('is_return_with_notes');
            $table->dropColumn('approval_within_days');
            $table->dropColumn('approval_within_hours');
            $table->dropColumn('notify_by_email');
            $table->dropColumn('notify_by_whatsapp');
        });
            Schema::dropIfExists('process_step_action_takers');

    }
};
