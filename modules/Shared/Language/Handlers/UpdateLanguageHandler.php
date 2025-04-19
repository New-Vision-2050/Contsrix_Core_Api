<?php

declare(strict_types=1);

namespace Modules\Shared\Language\Handlers;

use Modules\Shared\Language\Commands\UpdateLanguageCommand;
use Modules\Shared\Language\Repositories\LanguageRepository;

class UpdateLanguageHandler
{
    public function __construct(
        private LanguageRepository $repository,
    ) {
    }

    public function handle(UpdateLanguageCommand $updateLanguageCommand)
    {
        $this->repository->updateLanguage($updateLanguageCommand->getId(), $updateLanguageCommand->toArray());
    }
}
