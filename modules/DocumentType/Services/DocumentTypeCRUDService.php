<?php

declare(strict_types=1);

namespace Modules\DocumentType\Services;

use Illuminate\Support\Collection;
use Modules\DocumentType\DTO\CreateDocumentTypeDTO;
use Modules\DocumentType\Models\DocumentType;
use Modules\DocumentType\Repositories\DocumentTypeRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class DocumentTypeCRUDService
{
    use HasExportService;

    public function __construct(
        private DocumentTypeRepository $repository,
    ) {
    }

    public function create(CreateDocumentTypeDTO $createDocumentTypeDTO): DocumentType
    {
         return $this->repository->createDocumentType($createDocumentTypeDTO->toArray());
    }

    public function list(array $filters, int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            filters: $filters,
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): DocumentType
    {
        return $this->repository->getDocumentType(
            id: $id,
        );
    }

    public function update(UuidInterface $id, array $data): bool
    {
        return $this->repository->updateDocumentType($id, $data);
    }

    public function delete(UuidInterface $id): bool
    {
        return $this->repository->deleteDocumentType($id);
    }

    public function getForExport(array $filters): Collection
    {
        return $this->repository->getForExport($filters);
    }
}
