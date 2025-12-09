<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTheme\Presenters;

use Modules\WebsiteCMS\WebsiteTheme\Models\WebsiteTheme;
use BasePackage\Shared\Presenters\AbstractPresenter;

class CurrentCompanyThemePresenter extends AbstractPresenter
{
    private WebsiteTheme $websiteTheme;

    public function __construct(WebsiteTheme $websiteTheme)
    {
        $this->websiteTheme = $websiteTheme;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->websiteTheme->id,
            'company_id' => $this->websiteTheme->company_id,
            'url' => $this->websiteTheme->url,
            'radius' => $this->websiteTheme->radius,
            'html_font_size' => $this->websiteTheme->html_font_size,
            'font_family' => $this->websiteTheme->font_family,
            'font_size' => $this->websiteTheme->font_size,
            'font_weight_light' => $this->websiteTheme->font_weight_light,
            'font_weight_regular' => $this->websiteTheme->font_weight_regular,
            'font_weight_medium' => $this->websiteTheme->font_weight_medium,
            'font_weight_bold' => $this->websiteTheme->font_weight_bold,
            'status' => $this->websiteTheme->status,
            'created_at' => $this->websiteTheme->created_at?->toDateTimeString(),
            'updated_at' => $this->websiteTheme->updated_at?->toDateTimeString(),
        ];

        // Add color palettes with only the attributes specified for each slug
        if ($this->websiteTheme->relationLoaded('colorPalettes')) {
            $data['color_palettes'] = [];
            foreach ($this->websiteTheme->colorPalettes as $palette) {
                $paletteData = [];

                // Get the attributes array for this palette
                $attributes = $palette->attributes;

                // Ensure attributes is an array (decode if it's a JSON string)
                if (is_string($attributes)) {
                    $attributes = json_decode($attributes, true) ?? [];
                } elseif (!is_array($attributes)) {
                    $attributes = [];
                }

                // Add only the columns specified in attributes
                foreach ($attributes as $attribute) {
                    // Map attribute names to actual column values
                    $paletteData[$attribute] = $this->getAttributeValue($palette, $attribute);
                }

                $data['color_palettes'][$palette->slug] = $paletteData;
            }
        }

        // Add icon if loaded
        if ($this->websiteTheme->relationLoaded('media')) {
            $iconMedia = $this->websiteTheme->getFirstMedia('icon');
            if ($iconMedia) {
                $data['icon_url'] = $iconMedia->getUrl();
            }
        }



        return $data;
    }

    /**
     * Get the value for a specific attribute from the palette
     */
    private function getAttributeValue($palette, string $attribute): ?string
    {
        // Direct column mapping
        $columnMap = [
            'primary' => 'primary',
            'light' => 'light',
            'dark' => 'dark',
            'contrast' => 'contrast',
            'secondary' => 'secondary',
            'divider' => 'divider',
            'disabled' => 'disabled',
            'black' => 'black',
            'white' => 'white',
            'paper' => 'paper',
            'default' => 'default',
        ];

        $column = $columnMap[$attribute] ?? $attribute;

        return $palette->{$column} ?? null;
    }
}
