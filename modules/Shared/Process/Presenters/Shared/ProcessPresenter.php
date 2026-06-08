<?php

declare(strict_types=1);

namespace Modules\Shared/Process\Presenters;

use Modules\Shared/Process\Models\Shared/Process;
use BasePackage\Shared\Presenters\AbstractPresenter;

class Shared/ProcessPresenter extends AbstractPresenter
{
    private Shared/Process $shared/Process;

    public function __construct(Shared/Process $shared/Process)
    {
        $this->shared/Process = $shared/Process;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->shared/Process->id,
            'name' => $this->shared/Process->name,
        ];
    }
}
