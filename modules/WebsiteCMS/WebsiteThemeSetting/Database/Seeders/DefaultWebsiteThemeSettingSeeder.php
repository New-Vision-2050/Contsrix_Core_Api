<?php

namespace Modules\WebsiteCMS\WebsiteThemeSetting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Shared\Media\Services\FileUploadService;
use Modules\WebsiteCMS\WebsiteThemeSetting\Models\WebsiteThemeSetting;
use Modules\WebsiteCMS\WebsiteThemeSetting\Models\WebsiteThemeSettingDepartment;

class DefaultWebsiteThemeSettingSeeder extends Seeder
{
    public function __construct(private FileUploadService $fileUploadService)
    {
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default theme setting
        $defaultTheme = WebsiteThemeSetting::firstOrCreate(
            ['is_default' => true],
            [
                'title' => [
                    'ar' => 'الثيم الافتراضي',
                    'en' => 'Default Theme',
                ],
                'description' => [
                    'ar' => 'هذا هو الثيم الافتراضي للموقع',
                    'en' => 'This is the default theme for the website',
                ],
                'about' => [
                    'ar' => 'معلومات عن الثيم الافتراضي',
                    'en' => 'Information about the default theme',
                ],
                'is_default' => true,
                'status' => 1,
            ]
        );

        //upload default theme image

        $path = resource_path() . "/images/default-theme.png";
        try {
            $file = new \Illuminate\Http\UploadedFile(
                $path,
                'new-vision-logo.png',
                null,
                null,
                true
            );

            $this->fileUploadService->uploadFile($defaultTheme, $file, 'website-theme-setting/main-image', "main_image", 'public');

        } catch (\Exception $exception) {

        }

        // Create default departments if theme was just created
        if ($defaultTheme->wasRecentlyCreated) {
            $departments = [
                [
                    'name' => [
                        'ar' => 'قسم المبيعات',
                        'en' => 'Sales Department',
                    ],
                ],
                [
                    'name' => [
                        'ar' => 'قسم التسويق',
                        'en' => 'Marketing Department',
                    ],
                ],
                [
                    'name' => [
                        'ar' => 'قسم الدعم الفني',
                        'en' => 'Technical Support Department',
                    ],
                ],
                [
                    'name' => [
                        'ar' => 'قسم الموارد البشرية',
                        'en' => 'Human Resources Department',
                    ],
                ],
            ];

            foreach ($departments as $departmentData) {
                WebsiteThemeSettingDepartment::create([
                    'website_theme_setting_id' => $defaultTheme->id,
                    'name' => $departmentData['name'],
                ]);
            }

            $this->command->info('Default theme setting with departments created successfully.');
        } else {
            $this->command->info('Default theme setting already exists.');
        }
    }
}
