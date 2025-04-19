<?php

declare(strict_types=1);

namespace Modules\Setting\Handlers;

use Modules\Setting\Commands\UpdateLoginWayCommand;
use Modules\Setting\Repositories\IdentifierSettingRepository;
use Modules\Setting\Repositories\LoginWayRepository;
use Modules\Setting\Repositories\SettingRepository;
use Ramsey\Uuid\UuidInterface;

class MakeIdentifierSettingDefaultHandler
{
    public function __construct(
        private IdentifierSettingRepository  $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->makeIdentifierSettingDefault($id);
    }

}
