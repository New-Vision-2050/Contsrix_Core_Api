<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Database\seeders;

use Illuminate\Database\Seeder;
use Modules\NotificationSettings\Models\NotificationSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DefaultNotificationSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // Create default notification setting for both email and SMS
            $defaultSetting = NotificationSettings::firstOrCreate(
                [

                    "company_id" => tenant("id")??"560005d6-04b8-53b3-9889-d312648288e3"
                ],
                [
                    'type' => 'both',
                    'email' => 'admin@constrix-nv.com',
                    'phone' => '0542138116',
                    'reminder_type' => 'weekly',
                    'message' => 'Default weekly notification reminder for system administrators.',
                    'is_active' => true,
                ]
            );


            DB::commit();

            $this->command->info('✅ Default notification settings created successfully');
            $this->command->info("📧 Email & SMS Setting: {$defaultSetting->id}");


            Log::info('Default notification settings seeded successfully', [
                'default_setting_id' => $defaultSetting->id,

            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            $this->command->error('❌ Failed to create default notification settings: ' . $e->getMessage());
            Log::error('Failed to seed default notification settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
