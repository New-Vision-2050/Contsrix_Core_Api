<?php

declare(strict_types=1);

namespace Modules\DocumentType\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\DocumentType\Models\DocumentType;
use App\Traits\HasExport;

/**
 * @property DocumentType $model
 * @method DocumentType findOneOrFail($id)
 * @method DocumentType findOneByOrFail(array $data)
 */
class DocumentTypeRepository extends BaseRepository
{
    use HasExport;

    public function __construct(DocumentType $model)
    {
        parent::__construct($model);
    }

    public function getDocumentTypeList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getDocumentType(UuidInterface $id): DocumentType
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createDocumentType(array $data): DocumentType
    {
        return $this->create($data);
    }

    public function updateDocumentType(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteDocumentType(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
