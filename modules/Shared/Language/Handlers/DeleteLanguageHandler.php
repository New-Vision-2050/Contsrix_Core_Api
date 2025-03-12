<?php

declare(strict_types=1);

namespace Modules\Shared\Language\Handlers;

use Modules\Shared\Language\Repositories\LanguageRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteLanguageHandler
{
    public function __construct(
        private LanguageRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteLanguage($id);
    }
}
