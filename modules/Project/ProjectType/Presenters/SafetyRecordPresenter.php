<?php

namespace Modules\Project\ProjectType\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Project\ProjectType\Models\SafetyRecord;

class SafetyRecordPresenter extends AbstractPresenter
{
    public function __construct(private SafetyRecord $model) {}

    protected function present(bool $isListing = false): array
    {
        $time = $this->model->time;
        if (is_string($time) && strlen($time) >= 5) {
            $time = substr($time, 0, 5);
        }

        $data = [
            'id' => $this->model->id,
            'project_id' => $this->model->project_id,
            'morphable' => $this->model->relationLoaded('morphable')
                ? [
                    'type' => $this->model->morphable_type,
                    'id' => $this->model->morphable_id,
                    'display' => $this->model->morphable?->name
                        ?? $this->model->morphable?->notification_number
                        ?? null,
                ]
                : null,
            'order_type' => $this->model->order_type,
            'date' => $this->model->date?->toDateString(),
            'time' => $time,
            'required_score' => $this->model->required_score,
            'earned_score' => $this->model->earned_score,
            'percentage' => $this->model->percentage,
            'consultant_engineer' => $this->model->consultant_engineer,
            'consultant' => $this->model->consultant,
            'contractor_id' => $this->model->contractor_id,
            'contractor_name' => $this->model->relationLoaded('contractor')
                ? ($this->model->contractor?->name ?? null)
                : null,
            'assigned_user' => $this->model->relationLoaded('assignedUser') && $this->model->assignedUser
                ? [
                    'id' => $this->model->assignedUser->id,
                    'name' => $this->model->assignedUser->name,
                ]
                : null,
            'status' => $this->model->status,
            'evidence' => $this->model->relationLoaded('media')
                ? $this->model->getMedia('violation_evidence')
                    ->map(fn ($media) => [
                        'id' => $media->id,
                        'name' => $media->name,
                        'file_name' => $media->file_name,
                        'mime_type' => $media->mime_type,
                        'size' => $media->size,
                        'url' => $media->getFullUrl(),
                    ])
                    ->values()
                    ->all()
                : [],
        ];

        if ($this->model->relationLoaded('all_violations')) {
            $data['all_violations'] = $this->model->all_violations->toArray();
        }

        return $data;
    }
}
