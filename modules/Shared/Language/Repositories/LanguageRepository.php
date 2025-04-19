<?php

declare(strict_types=1);

namespace Modules\Shared\Language\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\Language\Models\Language;

/**
 * @property Language $model
 * @method Language findOneOrFail($id)
 * @method Language findOneByOrFail(array $data)
 */
class LanguageRepository extends BaseRepository
{
    public function __construct(Language $model)
    {
        parent::__construct($model);
    }

    public function getLanguageList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getLanguage(UuidInterface $id): Language
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createLanguage(array $data): Language
    {
        return $this->create($data);
    }

    public function updateLanguage(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteLanguage(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
