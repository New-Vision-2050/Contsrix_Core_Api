<?php

declare(strict_types=1);

namespace Modules\Program\Presenters;

use Illuminate\Support\Str;
use Modules\Program\Models\Program;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\SubEntity\Presenters\SubEntityPresenter;

class ProgramSelectListPresenter extends AbstractPresenter
{
    private Program $program;

    public function __construct(Program $program)
    {
        $this->program = $program;
    }

    protected function present(bool $isListing = false): array
    {
        return array_merge(
            $this->transformProgram($this->program),
            [
                'children' => $this->program->children->map(fn($child) => $this->transformProgram($child)),
            ]
        );
    }

    private function transformProgram(Program $program): array
    {
        return [
            'id' => $program->id,
            'name' => $program->name[app()->getLocale()],
            'slug' => $program->slug,
            'is_active' => $program->is_active,
            'sub_entities' => $program->subEntities->filter(function ($sub) {
                return !Str::isUuid($sub->super_entity);
            }),
        ];
    }
}
