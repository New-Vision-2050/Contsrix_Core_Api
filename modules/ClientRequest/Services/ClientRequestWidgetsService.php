<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Services;

use Illuminate\Support\Facades\Cache;
use Modules\ClientRequest\Models\ClientRequest;
use Modules\ClientRequest\Repositories\ClientRequestRepository;
use Carbon\Carbon;
use Modules\ClientRequest\Presenters\ClientRequestWidgetsPresenter;

class ClientRequestWidgetsService
{
    protected $repository;

    public function __construct(ClientRequestRepository $repository)
    {
        $this->repository = $repository;
    }

    public function calculatePercentageChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    public function getClientPriceOfferStatistics()
    {
        $now = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        $currentStats = $this->getStatistics($now);
        $previousStats = $this->getStatistics($lastMonth);

        $totalCalculate = $this->calculatePercentageChange($currentStats['total'], $previousStats['total']);
        $pendingCalculate = $this->calculatePercentageChange($currentStats['pending'], $previousStats['pending']);
        $acceptedCalculate = $this->calculatePercentageChange($currentStats['accepted'], $previousStats['accepted']);
        $rejectedCalculate = $this->calculatePercentageChange($currentStats['rejected'], $previousStats['rejected']);

        return new ClientRequestWidgetsPresenter(
            $currentStats['total'],
            $currentStats['pending'],
            $currentStats['accepted'],
            $currentStats['rejected'],
            $totalCalculate,
            $pendingCalculate,
            $acceptedCalculate,
            $rejectedCalculate
        );
    }

    protected function getStatistics(Carbon $date)
    {
        $query = ClientRequest::where('company_id', tenant('id'))
            ->whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month);

        return [
            'total' => (clone $query)->where('status_client_request', 'accepted')->count(),
            'pending' => (clone $query)->where('client_price_offer_status', 'pending')->where('status_client_request', 'accepted')->count(),
            'accepted' => (clone $query)->where('client_price_offer_status', 'accepted')->where('status_client_request', 'accepted')->count(),
            'rejected' => (clone $query)->where('client_price_offer_status', 'rejected')->where('status_client_request', 'accepted')->count(),
        ];
    }

    public function clearWidgetCache(): void
    {
        Cache::forget('client_request_price_offer_widget_statistics-' . app()->getLocale());
    }
}
