<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Presenters;

use Modules\AdminRequest\Enum\AdminRequestStatus;
use Modules\AdminRequest\Models\AdminRequest;
use BasePackage\Shared\Presenters\AbstractPresenter;

class AdminRequestPresenter extends AbstractPresenter
{
    private AdminRequest $adminRequest;

    public function __construct(AdminRequest $adminRequest)
    {
        $this->adminRequest = $adminRequest;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->adminRequest->id,
            'user_name' => $this->adminRequest->user->name,
            "data" => $this->adminRequest->data,
            "action" => $this->adminRequest->action,
            "request_type" => $this->adminRequest->request_type,
            "status" => AdminRequestStatus::lang($this->adminRequest->status),
            "notes" => $this->adminRequest->notes,
//            "requestable" => $this->adminRequest->requestable,TODO add requestable with specific information dependant on request type
        ];
    }
}
