<?php

declare(strict_types=1);

namespace Modules\SubEntity\Repositories;

use Ramsey\Uuid\UuidInterface;
use Illuminate\Support\Facades\DB;
use Modules\SubEntity\Models\SubEntity;
use Illuminate\Database\Eloquent\Collection;
use BasePackage\Shared\Repositories\BaseRepository;

/**
 * @property SubEntity $model
 * @method SubEntity findOneOrFail($id)
 * @method SubEntity findOneByOrFail(array $data)
 */
class SubEntityRepository extends BaseRepository
{
    public function __construct(SubEntity $model)
    {
        parent::__construct($model);
    }

    public function getSubEntityList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getSubEntity(UuidInterface $id): SubEntity
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createSubEntity(array $data): SubEntity
    {
        return DB::transaction(function () use ($data) {
            $subEntity = $this->create($data);

            if (isset($data['children_allowed_registration_forms']) && filled($data['children_allowed_registration_forms'])) {
                $subEntity->allowedChildForms()->attach($data['children_allowed_registration_forms']);
            }

            return $subEntity;
        });
    }

    public function updateSubEntity(UuidInterface $id, array $data): bool
    {
        return DB::transaction(function () use ($id, $data) {
            $subEntity = $this->model->findOrFail($id);

            if (isset($data['children_allowed_registration_forms']) && filled($data['children_allowed_registration_forms'])) {
                $subEntity->allowedChildForms()->sync($data['children_allowed_registration_forms']);
            }

            return $subEntity->update($data);
        });
    }

    public function updateSubEntityAttributes(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteSubEntity(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function getPaginatedBySuperEntity(string $superEntityId, ?string $programSlug = null, ?string $entityName = null, ?string $registrationForm = null, int $page = 1, int $perPage = 15): array
    {
        $query = $this->model->newQuery()
            ->when($entityName, function ($q) use ($entityName) {
                return $q->where('name', $entityName);
            })
            ->where('origin_super_entity', $superEntityId)
            ->when($programSlug, function ($query) use ($programSlug) {
                return $query->whereHas('mainProgram', function ($q) use ($programSlug): void {
                    $q->where('slug', $programSlug);
                });
            })
            ->when(request()->has('name'), function ($query) {
                return $query->filter(['name' => request()->get('name')]);
            })
            ->when($registrationForm, function ($q) use ($registrationForm) {
                return $q->where('registration_form_id', $registrationForm);
            });

        $count = $query->count();
        $data = $query->forPage($page, $perPage)->orderBy('created_at', 'desc')->get();
        $pagination = $this->getPaginationInformation($page, $perPage, $count);

        return [
            'data' => $data,
            'pagination' => $pagination['pagination'],
        ];
    }

    public function getSelection(int $page = 1, int $perPage = 15): array
    {
        $query = $this->model->newQuery()
            ->select('id', 'name')
            ->active()
            ->where('is_registrable', true)
            ->when(request()->has('name'), function ($query) {
                return $query->filter(['name' => request()->get('name')]);
            });

        $count = $query->count();
        $data = $query->forPage($page, $perPage)->orderBy('created_at', 'desc')->get();
        $pagination = $this->getPaginationInformation($page, $perPage, $count);

        return [
            'data' => $data,
            'pagination' => $pagination['pagination'],
        ];
    }

    public function updateSubEntityStatus(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * @param string $id
     *
     * @return mixed
     */
    public function find($id)
    {
        return $this->model->find($id);
    }
}
