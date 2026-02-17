<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Presenters;

use Modules\Project\ProjectType\Models\Schema;
use BasePackage\Shared\Presenters\AbstractPresenter;

class SchemaPresenter extends AbstractPresenter
{
    private Schema $schema;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->schema->id,
            'name' => $this->schema->name,
        ];
    }
}
