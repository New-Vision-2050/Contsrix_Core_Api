<?php

declare(strict_types=1);

namespace Modules\Shared\RightTerminate\Presenters;

use Modules\Shared\RightTerminate\Models\RightTerminate;
use BasePackage\Shared\Presenters\AbstractPresenter;

class RightTerminatePresenter extends AbstractPresenter
{
    private RightTerminate $rightTerminate;

    public function __construct(RightTerminate $rightTerminate)
    {
        $this->rightTerminate = $rightTerminate;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->rightTerminate->id,
            'name' => $this->rightTerminate->name,
        ];
    }
}
