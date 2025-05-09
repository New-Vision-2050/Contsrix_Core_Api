<?php

declare(strict_types=1);

namespace Modules\Program\Presenters;

use Modules\Program\Models\Program;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ProgramPresenter extends AbstractPresenter
{
    private Program $program;

    public function __construct(Program $program)
    {
        $this->program = $program;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->program->id,
            'name' => $this->program->name[app()->getLocale()],
            'slug' => $this->program->slug,
        ];
    }
}
