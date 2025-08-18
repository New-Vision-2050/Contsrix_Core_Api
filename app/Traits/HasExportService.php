<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Collection;

trait HasExportService
{
    /**
     * Get data for export through repository
     *
     * @param array $filters
     * @return Collection
     */
    public function getForExport(array $filters = []): Collection
    {
        return $this->repository->getForExport($filters);
    }
}
