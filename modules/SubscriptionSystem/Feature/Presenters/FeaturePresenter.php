<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Feature\Presenters;

use Modules\SubscriptionSystem\Feature\Models\Feature;
use BasePackage\Shared\Presenters\AbstractPresenter;

class FeaturePresenter extends AbstractPresenter
{
    private Feature $feature;

    public function __construct(Feature $feature)
    {
        $this->feature = $feature;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->feature->id,
            'name' => $this->feature->name,
            'featureable_id' => $this->feature->featureable_id,
            'featureable_type' => $this->feature->featureable_type,
            'featureable' => $this->feature->featureable
        ];
    }
}
