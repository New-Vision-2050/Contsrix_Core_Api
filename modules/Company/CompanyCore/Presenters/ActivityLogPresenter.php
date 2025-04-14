<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Presenters;

use Modules\ActivityLog\Models\ActivityLog;
use Modules\Company\CompanyCore\Models\Company;
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
            'action' => $this->activityLog->action,
            'date' => $this->activityLog->date,
            'user' => $this->activityLog->user->name,

        ];
    }
}
