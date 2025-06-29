<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Modules\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Modules\RoleAndPermission\Models\Permission;
use Modules\SubscriptionSystem\Feature\Models\Feature;
use Modules\SubscriptionSystem\Feature\Models\FeaturePermission;
use Modules\SubscriptionSystem\Modules\Models\Module;

class ModuleStructureSeeder extends Seeder
{
    public function run(): void
    {
        $createModule = function (array $name, string $slug, ?string $parentId = null) {
            return Module::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'slug' => $slug,
                    'parent_id' => $parentId,
                ]
            );
        };

        $createFeature = function (array $name, string $slug, string $moduleId) {
            return Feature::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'slug' => $slug,
                    'module_id' => $moduleId
                ]
            );
        };

        $createFeaturePermission = function ($feature, array $permissionNames) {
            $permissions = Permission::whereIn('name', $permissionNames)->get();

            foreach ($permissions as $permission) {
                FeaturePermission::updateOrCreate(
                    [
                        'feature_id' => $feature->id,
                        'permission_id' => $permission->id
                    ],
                    [
                        'feature_id' => $feature->id,
                        'permission_id' => $permission->id
                    ]
                );
            }

            return true;
        };

        // Process all permissions and create module/feature structure
        $this->buildModuleStructureFromPermissions($createModule, $createFeature, $createFeaturePermission);

        // Additional modules and features (that may not have permissions yet)
        $this->seedAdditionalModules($createModule, $createFeature);
    }

    /**
     * Build module structure based on existing permissions
     */
    private function buildModuleStructureFromPermissions($createModule, $createFeature, $createFeaturePermission): void
    {
        // Get all permissions from the database
        $allPermissions = Permission::all()->pluck('name');

        // Group permissions by module and feature
        $groupedPermissions = $this->groupPermissionsByModuleAndFeature($allPermissions);

        // Process each module
        foreach ($groupedPermissions as $moduleSlug => $features) {
            // Create module with English and Arabic names from translation files
            $module = $createModule([
                'en' => $this->getTranslatedName($moduleSlug, 'en'),
                'ar' => $this->getTranslatedName($moduleSlug, 'ar')
            ], $moduleSlug);

            // Process each feature in the module
            foreach ($features as $featureSlug => $permissions) {
                // Create feature with English and Arabic names from translation files
                $feature = $createFeature([
                    'en' => $this->getTranslatedName($featureSlug, 'en'),
                    'ar' => $this->getTranslatedName($featureSlug, 'ar')
                ], $featureSlug, $module->id);

                // Associate permissions with the feature
                $createFeaturePermission($feature, $permissions);
            }
        }
    }

    /**
     * Group permissions by module and feature
     */
    private function groupPermissionsByModuleAndFeature(Collection $permissions): array
    {
        $result = [];

        foreach ($permissions as $permission) {
            $parts = explode('.', $permission);

            // Only process permissions with the format module.feature.action
            if (count($parts) >= 3) {
                $moduleSlug = $parts[0];
                $featureSlug = $parts[1];

                if (!isset($result[$moduleSlug])) {
                    $result[$moduleSlug] = [];
                }

                if (!isset($result[$moduleSlug][$featureSlug])) {
                    $result[$moduleSlug][$featureSlug] = [];
                }

                $result[$moduleSlug][$featureSlug][] = $permission;
            }
        }

        return $result;
    }

    /**
     * Get translated name for a slug using lang files
     *
     * @param string $slug The slug to translate
     * @param string $locale The locale to use (en/ar)
     * @return string The translated name
     */
    private function getTranslatedName(string $slug, string $locale): string
    {
        // Save current app locale
        $currentLocale = App::getLocale();

        // Set locale for translation
        App::setLocale($locale);

        // Get translation using __() helper
        $translated = __('names.' . $slug, [], $locale);

        // If translation doesn't exist, fallback to formatted slug
        if ($translated === 'names.' . $slug) {
            $translated = Str::title(str_replace('-', ' ', $slug));
        }

        // Restore original locale
        App::setLocale($currentLocale);

        return $translated;
    }

    /**
     * Seed additional modules and features that may not be associated with permissions yet
     */
    private function seedAdditionalModules($createModule, $createFeature): void
    {
        // Program Management module
        $programsRoot = $createModule([
            'en' => $this->getTranslatedName('program-management', 'en'),
            'ar' => $this->getTranslatedName('program-management', 'ar')
        ], 'program-management');

        // Program Management features
        $programFeatures = [
            'program-management-sub-tables',
            'program-management-main-tables-table-structure',
            'program-management-main-tables-table-content',
            'program-management-main-tables-table-settings'
        ];

        foreach ($programFeatures as $slug) {
            $createFeature([
                'en' => $this->getTranslatedName($slug, 'en'),
                'ar' => $this->getTranslatedName($slug, 'ar')
            ], $slug, $programsRoot->id);
        }

        // Settings module
        $settingsRoot = $createModule([
            'en' => $this->getTranslatedName('settings', 'en'),
            'ar' => $this->getTranslatedName('settings', 'ar')
        ], 'settings');

        // User Profile Settings submodule
        $userProfileSettings = $createModule([
            'en' => $this->getTranslatedName('user-profile-settings', 'en'),
            'ar' => $this->getTranslatedName('user-profile-settings', 'ar')
        ], 'user-profile-settings', $settingsRoot->id);

        // User Profile module
        $userProfile = $createModule([
            'en' => $this->getTranslatedName('user-profile', 'en'),
            'ar' => $this->getTranslatedName('user-profile', 'ar')
        ], 'user-profile', $userProfileSettings->id);

        // User Profile features
        $userProfileFeatures = [
            'personal-data',
            'professional-data',
            'bank-data',
            'activities'
        ];

        foreach ($userProfileFeatures as $slug) {
            $createFeature([
                'en' => $this->getTranslatedName($slug, 'en'),
                'ar' => $this->getTranslatedName($slug, 'ar')
            ], $slug, $userProfile->id);
        }
    }

    /**
     * Example of how to manually integrate specific permissions with features
     * Can be used after the automatic structure is built
     */
    private function integrateSpecificPermissions($createFeaturePermission): void
    {
        // Example: Find an existing feature and associate specific permissions with it
        $employeeFeature = Feature::where('slug', 'employee')->first();

        if ($employeeFeature) {
            $createFeaturePermission($employeeFeature, [
                'users.employee.view',
                'users.employee.list',
                'users.employee.create',
                'users.employee.edit',
                'users.employee.delete',
                'users.employee.export'
            ]);
        }
    }

    /**
     * Utility method to get properly formatted permission display name
     * This follows the same pattern as seen in your code example
     */
    private function getFormattedPermissionName(string $permissionName, string $locale = null): string
    {
        $nameParts = explode('.', $permissionName);
        $translatedName = '';

        // Save current app locale
        $currentLocale = App::getLocale();

        // Set locale for translation if specified
        if ($locale) {
            App::setLocale($locale);
        }

        if (count($nameParts) >= 2) {
            // Skip the first part (module name) and translate the rest
            for ($i = count($nameParts) - 1; $i >= 1; $i--) {
                $translatedName .= ($translatedName ? ' ' : '') . __('names.' . $nameParts[$i]);
            }
        } elseif (count($nameParts) == 1) {
            $translatedName = __('names.' . $nameParts[0]);
        } else {
            $translatedName = __('names.' . $permissionName);
        }

        // Restore original locale
        if ($locale) {
            App::setLocale($currentLocale);
        }

        return $translatedName;
    }
}
