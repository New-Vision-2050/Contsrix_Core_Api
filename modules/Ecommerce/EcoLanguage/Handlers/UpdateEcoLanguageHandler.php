<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoLanguage\Handlers;

use Modules\Ecommerce\EcoLanguage\Commands\UpdateEcoLanguageCommand;
use Modules\Ecommerce\EcoLanguage\Repositories\EcoLanguageRepository;

class UpdateEcoLanguageHandler
{
    public function __construct(
        private EcoLanguageRepository $repository,
    ) {
    }

    public function handle(UpdateEcoLanguageCommand $updateEcoLanguageCommand)
    {
        $this->repository->updateEcoLanguage($updateEcoLanguageCommand->getId(), $updateEcoLanguageCommand->toArray());
    }
}
