<?php

declare(strict_types=1);

namespace Modules\Shared\Privilege\Presenters;

use Modules\Shared\Privilege\Models\Privilege;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Privilege\Services\PrivilegeCardConfigService;

class PrivilegePresenter extends AbstractPresenter
{
    private Privilege $privilege;
    private PrivilegeCardConfigService $cardConfigService;

    public function __construct(Privilege $privilege)
    {
        $this->privilege = $privilege;
        $this->cardConfigService = app(PrivilegeCardConfigService::class);
    }

    protected function present(bool $isListing = false): array
    {
        $data = [
            'id'   => $this->privilege->id,
            'name' => $this->privilege->name,
            'type' => $this->privilege->type,
        ];

        // Include card field configuration so the frontend knows which
        // fields to render for this privilege type.
        if ($this->privilege->type !== null) {
            $data['card_fields'] = $this->cardConfigService->getCardConfig($this->privilege->type);
        }

        return $data;
    }
}
