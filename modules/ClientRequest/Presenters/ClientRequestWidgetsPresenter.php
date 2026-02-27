<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;

class ClientRequestWidgetsPresenter extends AbstractPresenter
{
    private $total;
    private $pending;
    private $accepted;
    private $rejected;
    private $totalCalculate;
    private $pendingCalculate;
    private $acceptedCalculate;
    private $rejectedCalculate;

    public function __construct(
        $total,
        $pending,
        $accepted,
        $rejected,
        $totalCalculate,
        $pendingCalculate,
        $acceptedCalculate,
        $rejectedCalculate
    )
    {
        $this->total = $total;
        $this->pending = $pending;
        $this->accepted = $accepted;
        $this->rejected = $rejected;
        $this->totalCalculate = $totalCalculate;
        $this->pendingCalculate = $pendingCalculate;
        $this->acceptedCalculate = $acceptedCalculate;
        $this->rejectedCalculate = $rejectedCalculate;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            ['title' => __('lookups.total_client_requests'), 'code' => 'total_client_requests', 'total' => $this->total, 'percentage' => $this->totalCalculate],
            ['title' => __('lookups.pending_client_requests'), 'code' => 'pending_client_requests', 'total' => $this->pending, 'percentage' => $this->pendingCalculate],
            ['title' => __('lookups.accepted_client_requests'), 'code' => 'accepted_client_requests', 'total' => $this->accepted, 'percentage' => $this->acceptedCalculate],
            ['title' => __('lookups.rejected_client_requests'), 'code' => 'rejected_client_requests', 'total' => $this->rejected, 'percentage' => $this->rejectedCalculate],
        ];
    }
}
