<?php

declare(strict_types=1);

namespace Modules\Tenant\Presenters;

use BasePackage\Shared\Presenters\BasePresenter;
use Modules\Tenant\Models\Tenant;

class TenantPresenter extends BasePresenter
{
    public function __construct(private Tenant $tenant)
    {
        parent::__construct();
    }

    public function getData(): array
    {
        $data = [
            'id' => $this->tenant->id,
            'name' => $this->tenant->name,
            'created_at' => $this->tenant->created_at,
            'updated_at' => $this->tenant->updated_at,
        ];

        // Add company data if relationship is loaded
        if ($this->tenant->relationLoaded('company')) {
            $data['company'] = $this->tenant->company ? [
                'id' => $this->tenant->company->id,
                'name' => $this->tenant->company->name,
                'user_name' => $this->tenant->company->user_name,
                'email' => $this->tenant->company->email,
            ] : null;
        }

        // Add domains
        $domains = [];
        foreach ($this->tenant->domains as $domain) {
            $domains[] = [
                'id' => $domain->id,
                'domain' => $domain->domain,
            ];
        }
        $data['domains'] = $domains;

        return $data;
    }
}