<?php

declare(strict_types=1);

namespace Modules\Process\Services;

use Modules\Process\Contracts\WorkflowNotifier;

final class WorkflowNotifierRegistry
{
    /**
     * @var array<string, WorkflowNotifier>
     */
    private array $notifiers = [];

    public function register(string $processableType, WorkflowNotifier $notifier): void
    {
        $this->notifiers[$processableType] = $notifier;
    }

    public function for(string $processableType): ?WorkflowNotifier
    {
        return $this->notifiers[$processableType] ?? null;
    }
}
