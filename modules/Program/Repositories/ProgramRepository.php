<?php

declare(strict_types=1);

namespace Modules\Program\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Program\Models\Program;

/**
 * @property Program $model
 * @method Program findOneOrFail($id)
 * @method Program findOneByOrFail(array $data)
 */
class ProgramRepository extends BaseRepository
{
    public function __construct(Program $model)
    {
        parent::__construct($model);
    }

    public function getProgramList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getProgram(UuidInterface $id): Program
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createProgram(array $data): Program
    {
        return $this->create($data);
    }

    public function updateProgram(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteProgram(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
<<<<<<< HEAD
    public function selectList(): Collection
    {
        return $this->model::with([
            'subEntities:id,name,slug,main_program_id,super_entity,origin_super_entity,is_active',
            'children:id,name,slug,is_active,parent_id',
            'children.subEntities:id,name,slug,main_program_id,super_entity,origin_super_entity,is_active',
            'children.subEntities.children:id,name,slug,main_program_id,super_entity,origin_super_entity,is_active',
            'subEntities.children:id,name,slug,main_program_id,super_entity,origin_super_entity,is_active'
        ])
            ->select('id', 'name' ,'slug', 'is_active', 'parent_id')
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->get();
    }

=======
>>>>>>> 7be6c72c (merge with stage (first version ))
}
