<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Jobs;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Services\EmployeeTaskAutoCloseService;

class AutoCloseTaskAtDurationExpiryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $taskId,
        public readonly string $companyId,
        /** ISO 8601 with TZ offset — the SCHEDULED task end (time_from + duration_hours). NOT the max_over_time point. */
        public readonly string $closeAtIso,
    ) {}

    public function handle(EmployeeTaskAutoCloseService $service): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        tenancy()->initialize($this->companyId);

        try {
            $task = EmployeeTaskRequest::find($this->taskId);

            if (!$task) {
                return;
            }

            $closeAt = CarbonImmutable::parse($this->closeAtIso);
            $service->closeIfExpired($task, $closeAt, 'auto_duration');
        } finally {
            tenancy()->end();
        }
    }
}
