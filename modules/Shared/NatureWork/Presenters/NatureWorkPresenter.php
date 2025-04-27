<?php

declare(strict_types=1);

namespace Modules\Shared\NatureWork\Presenters;

use Modules\Shared\NatureWork\Models\NatureWork;
use BasePackage\Shared\Presenters\AbstractPresenter;

class NatureWorkPresenter extends AbstractPresenter
{
    private NatureWork $natureWork;

    public function __construct(NatureWork $natureWork)
    {
        $this->natureWork = $natureWork;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->natureWork->id,
            'name' => $this->natureWork->name,
        ];
    }
}
