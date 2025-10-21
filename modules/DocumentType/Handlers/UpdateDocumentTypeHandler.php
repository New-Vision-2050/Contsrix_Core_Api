<?php

declare(strict_types=1);

namespace Modules\DocumentType\Handlers;

use Modules\DocumentType\Commands\UpdateDocumentTypeCommand;
use Modules\DocumentType\Repositories\DocumentTypeRepository;

class UpdateDocumentTypeHandler
{
    public function __construct(
        private DocumentTypeRepository $repository,
    ) {
    }

    public function handle(UpdateDocumentTypeCommand $updateDocumentTypeCommand)
    {
        $this->repository->updateDocumentType($updateDocumentTypeCommand->getId(), $updateDocumentTypeCommand->toArray());
    }
}
