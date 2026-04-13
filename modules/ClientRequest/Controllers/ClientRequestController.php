<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\ClientRequest\Handlers\DeleteClientRequestHandler;
use Modules\ClientRequest\Handlers\UpdateClientRequestHandler;
use Modules\ClientRequest\Presenters\ClientRequestPresenter;
use Modules\ClientRequest\Presenters\ClientRequestMyRequestsPresenter;
use Modules\ClientRequest\Requests\CreateClientRequestRequest;
use Modules\ClientRequest\Requests\DeleteClientRequestRequest;
use Modules\ClientRequest\Requests\GetClientRequestListRequest;
use Modules\ClientRequest\Requests\GetClientRequestRequest;
use Modules\ClientRequest\Requests\UpdateClientRequestFullRequest;
use Modules\ClientRequest\Services\ClientRequestCRUDService;
use Modules\ClientRequest\Services\ClientRequestWidgetsService;
use Modules\ClientRequest\Services\ClientRequestStatusWidgetsService;
use Modules\ClientRequest\Exports\ClientRequestExport;
use Modules\ClientRequest\Requests\ExportClientRequestRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Cache;

class ClientRequestController extends Controller
{
    public function __construct(
        private ClientRequestCRUDService $clientRequestService,
        private UpdateClientRequestHandler $updateClientRequestHandler,
        private DeleteClientRequestHandler $deleteClientRequestHandler,
        private ClientRequestWidgetsService $clientRequestWidgetsService,
        private ClientRequestStatusWidgetsService $clientRequestStatusWidgetsService,
    ) {
    }

    public function index(GetClientRequestListRequest $request): JsonResponse
    {
        $list = $this->clientRequestService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(ClientRequestPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetClientRequestRequest $request): JsonResponse
    {
        $item = $this->clientRequestService->get(Uuid::fromString($request->route('id')));

        $presenter = new ClientRequestPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateClientRequestRequest $request): JsonResponse
    {
        $createdItem = $this->clientRequestService->create($request->createCreateClientRequestDTO());

        $this->clientRequestWidgetsService->clearWidgetCache();
        $this->clientRequestStatusWidgetsService->clearWidgetCache();

        $presenter = new ClientRequestPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateClientRequestFullRequest $request): JsonResponse
    {
        $command = $request->createUpdateClientRequestCommand();
        $this->updateClientRequestHandler->handle($command);

        $this->clientRequestWidgetsService->clearWidgetCache();
        $this->clientRequestStatusWidgetsService->clearWidgetCache();

        $item = $this->clientRequestService->get($command->getId());

        $presenter = new ClientRequestPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function updateFull(UpdateClientRequestFullRequest $request): JsonResponse
    {
        $updatedItem = $this->clientRequestService->update($request->createUpdateClientRequestDTO());

        $this->clientRequestWidgetsService->clearWidgetCache();
        $this->clientRequestStatusWidgetsService->clearWidgetCache();

        $presenter = new ClientRequestPresenter($updatedItem);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteClientRequestRequest $request): JsonResponse
    {
        $this->deleteClientRequestHandler->handle(Uuid::fromString($request->route('id')));

        $this->clientRequestWidgetsService->clearWidgetCache();
        $this->clientRequestStatusWidgetsService->clearWidgetCache();

        return Json::deleted();
    }

    /**
     * Export clientrequest to a file
     *
     * @param ExportClientRequestRequest $request
     */
    public function export(ExportClientRequestRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'client_request.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new ClientRequestExport($this->clientRequestService, $filters), $fileName);
    }

    public function getMyRequests(GetClientRequestListRequest $request): JsonResponse
    {
        $list = $this->clientRequestService->getMyRequests(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(ClientRequestMyRequestsPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function getPriceOfferWidgets(): JsonResponse
    {
        $cacheKey = 'client_request_price_offer_widget_statistics-' . app()->getLocale();

        $widgetData = Cache::remember($cacheKey, now()->addHours(1), function () {
            $presenter = $this->clientRequestWidgetsService->getClientPriceOfferStatistics();
            return $presenter->getData();
        });

        return Json::items($widgetData);
    }

    public function getStatusWidgets(): JsonResponse
    {
        $cacheKey = 'client_request_status_widget_statistics-' . app()->getLocale();

        $widgetData = Cache::remember($cacheKey, now()->addHours(1), function () {
            $presenter = $this->clientRequestStatusWidgetsService->getClientRequestStatusStatistics();
            return $presenter->getData();
        });

        return Json::items($widgetData);
    }
}
