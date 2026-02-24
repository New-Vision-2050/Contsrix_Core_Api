<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\ClientRequest\Handlers\DeleteClientRequestHandler;
use Modules\ClientRequest\Handlers\UpdateClientRequestHandler;
use Modules\ClientRequest\Presenters\ClientRequestPresenter;
use Modules\ClientRequest\Requests\CreateClientRequestRequest;
use Modules\ClientRequest\Requests\DeleteClientRequestRequest;
use Modules\ClientRequest\Requests\GetClientRequestListRequest;
use Modules\ClientRequest\Requests\GetClientRequestRequest;
use Modules\ClientRequest\Requests\UpdateClientRequestRequest;
use Modules\ClientRequest\Services\ClientRequestCRUDService;
use Modules\ClientRequest\Exports\ClientRequestExport;
use Modules\ClientRequest\Requests\ExportClientRequestRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class ClientRequestController extends Controller
{
    public function __construct(
        private ClientRequestCRUDService $clientRequestService,
        private UpdateClientRequestHandler $updateClientRequestHandler,
        private DeleteClientRequestHandler $deleteClientRequestHandler,
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

        $presenter = new ClientRequestPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateClientRequestRequest $request): JsonResponse
    {
        $command = $request->createUpdateClientRequestCommand();
        $this->updateClientRequestHandler->handle($command);

        $item = $this->clientRequestService->get($command->getId());

        $presenter = new ClientRequestPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteClientRequestRequest $request): JsonResponse
    {
        $this->deleteClientRequestHandler->handle(Uuid::fromString($request->route('id')));

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
}
