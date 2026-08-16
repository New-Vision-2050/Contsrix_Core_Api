<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\OrderPermit;

/**
 * @property OrderPermit $model
 * @method OrderPermit findOneOrFail($id)
 */
class OrderPermitRepository extends BaseRepository
{
    public function __construct(OrderPermit $model)
    {
        parent::__construct($model);
    }

    public function list(array $conditions = [], string $orderBy = 'id', string $sortBy = 'asc', ?string $name = null): Collection
    {
        $query = $this->model->with(['department', 'orderPermitType']);

        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        if ($name !== null && $name !== '') {
            $query->whereHas('udsRecords', static function ($udsQuery) use ($name): void {
                $udsQuery->where('name', $name);
            });
        }

        return $query->orderBy($orderBy, $sortBy)->get();
    }
}
