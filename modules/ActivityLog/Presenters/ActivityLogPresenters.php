<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Presenters;

use Modules\ActivityLog\Models\ActivityLog;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ActivityLogPresenter extends AbstractPresenter
{
    private ActivityLog $activityLog;

    public function __construct(ActivityLog $activityLog)
    {
        $this->activityLog = $activityLog;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->activityLog->id,
            'name' => $this->activityLog->name,
        ];
    }
}
