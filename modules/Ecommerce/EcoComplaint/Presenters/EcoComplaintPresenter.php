<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoComplaint\Presenters;

use Modules\Ecommerce\EcoComplaint\Models\EcoComplaint;
use BasePackage\Shared\Presenters\AbstractPresenter;

class EcoComplaintPresenter extends AbstractPresenter
{
    private EcoComplaint $ecoComplaint;

    public function __construct(EcoComplaint $ecoComplaint)
    {
        $this->ecoComplaint = $ecoComplaint;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->ecoComplaint->id,
            'name' => $this->ecoComplaint->name,
            'status' => $this->ecoComplaint->status,
        ];
    }
}
