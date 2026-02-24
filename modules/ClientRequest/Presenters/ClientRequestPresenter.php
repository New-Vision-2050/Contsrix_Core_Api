<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Presenters;

use Modules\ClientRequest\Models\ClientRequest;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ClientRequestPresenter extends AbstractPresenter
{
    private ClientRequest $clientRequest;

    public function __construct(ClientRequest $clientRequest)
    {
        $this->clientRequest = $clientRequest;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->clientRequest->id,
            'name' => $this->clientRequest->name,
        ];
    }
}
