<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Exceptions;

use RuntimeException;

class ProjectNotificationException extends RuntimeException
{
    public static function notFound(string $id): self
    {
        return new self("Project notification [{$id}] not found.");
    }

    public static function cannotApprove(string $status): self
    {
        return new self("Cannot approve a notification with status [{$status}].");
    }

    public static function cannotReject(string $status): self
    {
        return new self("Cannot reject a notification with status [{$status}].");
    }

    public static function taskTypeNotFound(): self
    {
        return new self('Project notification EmployeeTaskType not found. Ensure the EmployeeTaskTypeSeeder has been run.');
    }

    public static function linkedTaskNotFound(string $id): self
    {
        return new self("Project notification [{$id}] has no linked employee task.");
    }

    public static function procedureNotAvailable(): self
    {
        return new self('The requested procedure is not currently available for this notification.');
    }
}
