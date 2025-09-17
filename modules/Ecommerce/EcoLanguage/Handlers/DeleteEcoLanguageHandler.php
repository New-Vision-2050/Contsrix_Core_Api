<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoLanguage\Handlers;

use Modules\Ecommerce\EcoLanguage\Repositories\EcoLanguageRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoLanguageHandler
{
    public function __construct(
        private EcoLanguageRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteEcoLanguage($id);
    }
}
