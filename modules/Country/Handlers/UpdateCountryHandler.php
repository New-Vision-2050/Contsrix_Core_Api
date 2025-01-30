<?php

declare(strict_types=1);

namespace Modules\Country\Handlers;

use Modules\Country\Commands\UpdateCountryCommand;
use Modules\Country\Repositories\CountryRepository;

class UpdateCountryHandler
{
    public function __construct(
        private CountryRepository $repository,
    ) {
    }

    public function handle(UpdateCountryCommand $updateCountryCommand)
    {
        $this->repository->updateCountry($updateCountryCommand->getId(), $updateCountryCommand->toArray());
    }
}
