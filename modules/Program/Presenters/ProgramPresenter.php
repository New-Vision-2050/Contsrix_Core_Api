<?php

declare(strict_types=1);

namespace Modules\Program\Presenters;

use Modules\Program\Models\Program;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\SubEntity\Presenters\SubEntityPresenter;

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
            'features' => $this->getFeatures(),

        ];
    }
    public function getFeatures(): array
    {
        return $this->program->features->map(function ($feature) {
            return [
                'id' => $feature->id,
                'name' => $feature->name,
                'slug' => $feature->slug,
                'created_at' => $feature->created_at?->toIso8601String(),
            ];
        })->toArray();
    }
    protected function getWithSubEntities(bool $isListing = false): array
    {
        return [
            'id' => $this->program->id,
            'name' => $this->program->name[app()->getLocale()],
            'slug' => $this->program->slug,
            'sub_entities' => SubEntityPresenter::collection($this->program->subEntities),
            'features' => $this->getFeatures(),

        ];
    }

    public static function collectionWithSubEntities(iterable $collection, ...$additionalParams): array
    {
        $result = [];
        foreach ($collection as $item) {
            $data = (new static($item, ...$additionalParams))->getWithSubEntities(isListing: true);

            if ($data === null) {
                continue;
            }

            $result[] = $data;
        }

        return $result;
    }
}
