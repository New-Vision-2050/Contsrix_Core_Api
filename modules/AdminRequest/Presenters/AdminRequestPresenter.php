<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Presenters;

use Carbon\Carbon;
use Modules\AdminRequest\Enum\AdminRequestStatus;
use Modules\AdminRequest\Models\AdminRequest;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Media\Presenters\MediaPresenter;
use Stevebauman\Location\Facades\Location;

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
            'serial_number' => $this->adminRequest->serial_number,
            'user_name' => $this->adminRequest->user->name,
            "data" => $this->adminRequest->data,
            "action" => $this->adminRequest->action,
            "request_type" => $this->adminRequest->request_type,
            "status" => $this->adminRequest->status,
            "notes" => $this->adminRequest->notes,
            "company_name"=> $this->adminRequest->company->name,
            "attachments"=>MediaPresenter::collection( $this->adminRequest->getMedia("upload")),
            "created_at" => Carbon::parse($this->adminRequest->created_at)->setTimezone(getTimeZoneBranchByRequest())->format('Y-m-d H:i:s')
//            "requestable" => $this->adminRequest->requestable,TODO add requestable with specific information dependant on request type
        ];
    }
}
