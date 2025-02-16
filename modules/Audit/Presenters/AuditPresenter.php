<?php

declare(strict_types=1);

namespace Modules\Audit\Presenters;

use Modules\Audit\Models\Audit;
use BasePackage\Shared\Presenters\AbstractPresenter;

class AuditPresenter extends AbstractPresenter
{
    private Audit $audit;

    public function __construct(Audit $audit)
    {
        $this->audit = $audit;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->audit->id,
            'user' => [
                'id' => $this->audit->user->id,
                'name' => $this->audit->user->name,
                'email' => $this->audit->user->email,
            ],
            'event' => $this->audit->event,
            'auditable_id' => $this->audit->auditable_id,
            'auditable_type' => $this->audit->auditable_type,
            'url' => $this->audit->url,
            'ip_address' => $this->audit->ip_address,
            'user_agent' => $this->audit->user_agent,
            'tags' => $this->audit->tags,
            'created_at' => $this->audit->created_at,
            'updated_at' => $this->audit->updated_at,
        ];
    }
}
