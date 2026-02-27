<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Presenters;

use Modules\ClientRequest\Models\ClientRequestService;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ClientRequestServicePresenter extends AbstractPresenter
{
    private ClientRequestService $service;

    public function __construct(ClientRequestService $service)
    {
        $this->service = $service;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->service->id,
            'name' => $this->service->name,
            'type' => $this->service->type,
            'created_at' => $this->service->created_at?->toDateTimeString(),
            'updated_at' => $this->service->updated_at?->toDateTimeString(),
        ];
    }
}
