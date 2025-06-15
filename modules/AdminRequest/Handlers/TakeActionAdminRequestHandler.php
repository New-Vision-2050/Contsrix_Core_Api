<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Handlers;

use Modules\AdminRequest\Commands\TakeActionOnAdminRequestCommand;
use Modules\AdminRequest\Enum\AdminRequestStatus;
use Modules\AdminRequest\Repositories\AdminRequestRepository;

class TakeActionAdminRequestHandler
{
    public function __construct(
        private AdminRequestRepository $repository,
    )
    {
    }

    public function handle(TakeActionOnAdminRequestCommand $takeActionOnAdminRequestCommand)
    {
        try {
            $adminRequest = $this->repository->findOneByOrFail(["id"=>$takeActionOnAdminRequestCommand->getId()]);
        }catch (\Exception $e) {
            throw new \Exception(__("validation.not-found"), 404);
        }
        if($adminRequest->status != AdminRequestStatus::PENDING->value){
            throw new \Exception(__("validation.action-took-before"), 400);

        }
        if ($takeActionOnAdminRequestCommand->getStatus() == AdminRequestStatus::ACTIVE->value) {
            $this->repository->acceptActionOnAdminRequest($takeActionOnAdminRequestCommand->getId(), $takeActionOnAdminRequestCommand->getStatus());

        } elseif ($takeActionOnAdminRequestCommand->getStatus() == AdminRequestStatus::INACTIVE->value) {
           $this->repository->rejectActionOnAdminRequest($takeActionOnAdminRequestCommand->getId(), $takeActionOnAdminRequestCommand->getStatus());

        }
    }


}
