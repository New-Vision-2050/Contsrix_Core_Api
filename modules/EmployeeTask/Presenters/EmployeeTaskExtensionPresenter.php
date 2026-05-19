<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Presenters;

use Modules\Attendance\Support\HoursFormatter;
use Modules\EmployeeTask\Models\EmployeeTaskExtensionRequest;

final class EmployeeTaskExtensionPresenter
{
    public function __construct(private readonly EmployeeTaskExtensionRequest $extension) {}

    public function toArray(): array
    {
        $e = $this->extension;

        return [
            'id'                       => $e->id,
            'employee_task_request_id' => $e->employee_task_request_id,
            'additional_hours'         => HoursFormatter::fromDecimalString($e->additional_hours),
            'reason'                   => $e->reason,
            'status'                   => $e->status,
            'review_notes'             => $e->review_notes,
            'reviewed_at'              => $e->reviewed_at?->format('Y-m-d H:i:s'),
            'created_at'               => $e->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public static function collection(iterable $extensions): array
    {
        $result = [];
        foreach ($extensions as $ext) {
            $result[] = (new self($ext))->toArray();
        }
        return $result;
    }
}
