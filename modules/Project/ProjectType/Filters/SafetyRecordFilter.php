<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;

class SafetyRecordFilter extends SearchModelFilter
{
    public $relations = [];

    public function search($search)
    {
        return $this->where(function ($query) use ($search) {
            $query->whereHasMorph(
                'morphable',
                [ProjectNotification::class],
                fn ($morphQuery) => $morphQuery->where('notification_number', 'like', '%'.$search.'%')
            )->orWhereHasMorph(
                'morphable',
                [ProjectOrderPermit::class],
                fn ($morphQuery) => $morphQuery->where('name', 'like', '%'.$search.'%')
            );
        });
    }

    public function date($date)
    {
        return $this->whereDate('date', $date);
    }

    public function consultantEngineer($consultantEngineer)
    {
        return $this->where('consultant_engineer', 'like', '%'.$consultantEngineer.'%');
    }

    public function consultant($consultant)
    {
        return $this->where('consultant', 'like', '%'.$consultant.'%');
    }

    public function contractorId($contractorId)
    {
        return $this->where('contractor_id', $contractorId);
    }

    public function status($status)
    {
        return $this->where('status', $status);
    }

    public function assignedUserId($assignedUserId)
    {
        return $this->where('assigned_user_id', $assignedUserId);
    }
}
