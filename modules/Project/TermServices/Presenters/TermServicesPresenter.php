<?php

declare(strict_types=1);

namespace Modules\Project\TermServices\Presenters;

use Modules\Project\TermServices\Models\TermServices;
use BasePackage\Shared\Presenters\AbstractPresenter;

class TermServicesPresenter extends AbstractPresenter
{
    private TermServices $termServices;

    public function __construct(TermServices $termServices)
    {
        $this->termServices = $termServices;
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id' => $this->termServices->id,
            'name' => $this->termServices->name,
            'is_active' => $this->termServices->is_active,
            'term_settings_count' => $this->termServices->termSettings()->count(),
            'created_at' => $this->termServices->created_at?->toDateTimeString(),
            'updated_at' => $this->termServices->updated_at?->toDateTimeString(),
        ];

        if (!$isListing && $this->termServices->relationLoaded('termSettings')) {
            $data['term_settings'] = $this->termServices->termSettings->map(function ($setting) {
                return [
                    'id' => $setting->id,
                    'name' => $setting->name,
                    'description' => $setting->description,
                    'parent_id' => $setting->parent_id,
                    'children_count' => $setting->children()->count(),
                ];
            });
        }

        return $data;
    }
}
