<?php

declare(strict_types=1);

namespace Modules\Process\Presenters;

use Modules\Process\Models\Process;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ProcessPresenter extends AbstractPresenter
{
    private Process $process;

    public function __construct(Process $process)
    {
        $this->process = $process;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->process->id,
            'name' => $this->process->name,
        ];
    }
}
