<?php

namespace Modules\WebsiteCMS\WebsiteTheme\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Models\Domain;
use Modules\WebsiteCMS\WebsiteTheme\Models\WebsiteTheme;
use Modules\WebsiteCMS\WebsiteTheme\Models\WebsiteColorPalette;

class WebsiteThemeSeeder extends Seeder
{

    private function parseDomain($username)
    {
        $url = request()->header("X-DOMAIN")??request()->host();
        if (substr_count($url, '.') > 1) {
            $urlParts = explode(".", $url);
            $subDomain = $urlParts[0] . "-" . $username;
            $url = $subDomain . "." . $urlParts[1] . "." . $urlParts[2];
        } else {
            $url = $username . "." . $url;
        }
        return $url;
    }
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyId = tenant('id');

        if (!$companyId) {
            $this->command->error('No tenant context found. This seeder must run within a tenant context.');
            return;
        }

        DB::transaction(function () use ($companyId) {
            // Check if theme already exists for this company
            $existingTheme = WebsiteTheme::where('company_id', $companyId)->first();

            if ($existingTheme) {
                $this->command->info("WebsiteTheme already exists for company: {$companyId}");
                return;
            }
            $domain = Company::query()->where('id', $companyId)->first()->domains()->first()?->domain;

            if (!$domain) {
                $domain = $this->parseDomain(tenant('user_name'));
            }

            if (str_contains($domain, "core")) {
                $domain = str_replace("core", "website", $domain);

            } else {
                $domain = "website-" . $domain;
            }

            // Create WebsiteTheme
            $theme = WebsiteTheme::create([
                'company_id' => $companyId,
                'url' => $domain,
                'radius' => 8,
                'html_font_size' => 16,
                'font_family' => 'Roboto, Arial, sans-serif',
                'font_size' => '14px',
                'font_weight_light' => '300',
                'font_weight_regular' => '400',
                'font_weight_medium' => '500',
                'font_weight_bold' => '700',
                'status' => 1,
            ]);

            $this->command->info("Created WebsiteTheme for company: {$companyId}");

            // Define color palettes with slugs
            $colorPalettes = [
                [
                    'name' => 'Common (black and white)',
                    'slug' => 'common',
                    'attributes' => ["black", "white"],
                    'primary' => null,
                    'light' => '#FFFFFF',
                    'dark' => '#000000',
                    'contrast' => null
                ],
                [
                    'name' => 'Primary Color',
                    'slug' => 'primary',
                    'attributes' => ["primary", "light", "dark", "contrast"],
                    'primary' => '#1976D2',
                    'light' => '#42A5F5',
                    'dark' => '#1565C0',
                    'contrast' => '#FFFFFF',
                ],
                [
                    'name' => 'Secondary Color',
                    'slug' => 'secondary',
                    'attributes' => ["primary", "light", "dark", "contrast"],
                    'primary' => '#9C27B0',
                    'light' => '#BA68C8',
                    'dark' => '#7B1FA2',
                    'contrast' => '#FFFFFF',
                ],
                [
                    'name' => 'Info Color',
                    'slug' => 'info',
                    'attributes' => ["primary", "light", "dark", "contrast"],
                    'primary' => '#0288D1',
                    'light' => '#03A9F4',
                    'dark' => '#01579B',
                    'contrast' => '#FFFFFF',
                ],
                [
                    'name' => 'Warning Color',
                    'slug' => 'warning',
                    'attributes' => ["primary", "light", "dark", "contrast"],
                    'primary' => '#ED6C02',
                    'light' => '#FF9800',
                    'dark' => '#E65100',
                    'contrast' => '#FFFFFF',
                ],
                [
                    'name' => 'Error Color',
                    'slug' => 'error',
                    'attributes' => ["primary", "light", "dark", "contrast"],
                    'primary' => '#D32F2F',
                    'light' => '#EF5350',
                    'dark' => '#C62828',
                    'contrast' => '#FFFFFF',
                ],
                [
                    'name' => 'Text Color',
                    'slug' => 'text',
                    'attributes' => ["primary", "secondary", "divider", "disabled"],
                    'primary' => '#212121',
                    'light' => '#757575',
                    'dark' => '#000000',
                    'contrast' => '#FFFFFF',
                ],
                [
                    'name' => 'Background',
                    'slug' => 'background',
                    'attributes' => ["paper", "default"],
                    'primary' => null,
                    'light' => '#F5F5F5',
                    'dark' => '#EEEEEE',
                    'contrast' => null,
                ],
                [
                    'name' => 'Success',
                    'slug' => 'success',
                    'attributes' => ["primary", "light", "dark", "contrast"],
                    'primary' => '#212121',
                    'light' => '#757575',
                    'dark' => '#000000',
                    'contrast' => '#FFFFFF',
                ],
            ];

            // Create color palettes
            foreach ($colorPalettes as $palette) {
                $paletteData = [
                    'website_theme_id' => $theme->id,
                    'name' => $palette['name'],
                    'slug' => $palette['slug'],
                    'attributes' => json_encode($palette['attributes']),
                    'primary' => $palette['primary'],
                    'light' => $palette['light'],
                    'dark' => $palette['dark'],
                    'contrast' => $palette['contrast'],
                ];

                // Add additional fields based on attributes
                foreach ($palette['attributes'] as $attr) {
                    if (isset($palette[$attr])) {
                        $paletteData[$attr] = $palette[$attr];
                    }
                }

                // Map specific attributes to columns
                if (in_array('black', $palette['attributes'])) {
                    $paletteData['black'] = $palette['dark'] ?? '#000000';
                }
                if (in_array('white', $palette['attributes'])) {
                    $paletteData['white'] = $palette['light'] ?? '#FFFFFF';
                }
                if (in_array('secondary', $palette['attributes'])) {
                    $paletteData['secondary'] = $palette['light'] ?? null;
                }
                if (in_array('divider', $palette['attributes'])) {
                    $paletteData['divider'] = $palette['light'] ?? null;
                }
                if (in_array('disabled', $palette['attributes'])) {
                    $paletteData['disabled'] = $palette['contrast'] ?? null;
                }
                if (in_array('paper', $palette['attributes'])) {
                    $paletteData['paper'] = $palette['light'] ?? null;
                }
                if (in_array('default', $palette['attributes'])) {
                    $paletteData['default'] = $palette['dark'] ?? null;
                }

                WebsiteColorPalette::create($paletteData);
            }

            $this->command->info("Created " . count($colorPalettes) . " color palettes for WebsiteTheme");
        });
    }
}
