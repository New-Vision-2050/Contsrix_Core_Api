<?php

declare(strict_types=1);

namespace Modules\Shared\University\Presenters;

use Modules\Shared\University\Models\University;
use BasePackage\Shared\Presenters\AbstractPresenter;

class UniversityPresenter extends AbstractPresenter
{
    private University $university;

    public function __construct(University $university)
    {
        $this->university = $university;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->university->id,
            'name' => $this->university->name,
        ];
    }
}
