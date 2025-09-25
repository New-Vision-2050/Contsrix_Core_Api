<?php

declare(strict_types=1);

namespace Modules\DocumentType\Handlers;

use Modules\DocumentType\Repositories\DocumentTypeRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteDocumentTypeHandler
{
    public function __construct(
        private DocumentTypeRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteDocumentType($id);
    }
}
