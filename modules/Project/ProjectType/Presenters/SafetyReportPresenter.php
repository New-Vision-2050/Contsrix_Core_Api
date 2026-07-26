<?php

namespace Modules\Project\ProjectType\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;

class SafetyReportPresenter extends AbstractPresenter
{
    /**
     * @param  array<string, mixed>|object  $item
     */
    public function __construct(private array|object $item) {}

    protected function present(bool $isListing = false): array
    {
        $data = is_array($this->item) ? $this->item : (array) $this->item;

        return [
            'morphable_type' => $data['morphable_type'] ?? null,
            'morphable_id' => isset($data['morphable_id']) ? (string) $data['morphable_id'] : null,
            'morphable_display' => $data['morphable_display'] ?? null,
            'contractor_id' => $data['contractor_id'] ?? null,
            'contractor_name' => $data['contractor_name'] ?? null,
            'consultant_engineer' => $data['consultant_engineer'] ?? null,
            'consultant' => $data['consultant'] ?? null,
            'total_assignments' => (int) ($data['total_assignments'] ?? 0),
            'completed_count' => (int) ($data['completed_count'] ?? 0),
            'pending_count' => (int) ($data['pending_count'] ?? 0),
            'status' => $data['status'] ?? null,
        ];
    }
}
