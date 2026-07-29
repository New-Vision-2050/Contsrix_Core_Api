<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Carbon\Carbon;
use Modules\Project\ProjectType\Models\ProjectOrderPermitNoteLog;

class ProjectOrderPermitNoteLogPresenter extends AbstractPresenter
{
    public function __construct(private readonly ProjectOrderPermitNoteLog $model)
    {
    }

    protected function present(bool $isListing = false): array
    {
        $timezone = $this->model->timezone ?? 'Asia/Riyadh';

        $createdAt = $this->model->created_at
            ? Carbon::parse($this->model->created_at)->timezone($timezone)
            : null;

        return [
            'id' => $this->model->id,
            'project_order_permit_id' => $this->model->project_order_permit_id,
            'user_id' => $this->model->user_id,
            'user_name' => $this->model->user?->name,
            'created_by_name' => $this->model->created_by_name,
            'note' => $this->model->note,
            'type' => $this->model->type,
            'timezone' => $this->model->timezone,
            'created_at' => $createdAt?->toDateTimeString(),
            'created_at_date' => $createdAt?->toDateString(),
            'created_at_time' => $createdAt?->format('H:i:s'),
        ];
    }
}
