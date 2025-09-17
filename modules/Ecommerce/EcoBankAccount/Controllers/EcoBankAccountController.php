<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBankAccount\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoBankAccount\Handlers\DeleteEcoBankAccountHandler;
use Modules\Ecommerce\EcoBankAccount\Handlers\UpdateEcoBankAccountHandler;
use Modules\Ecommerce\EcoBankAccount\Presenters\EcoBankAccountPresenter;
use Modules\Ecommerce\EcoBankAccount\Requests\CreateEcoBankAccountRequest;
use Modules\Ecommerce\EcoBankAccount\Requests\DeleteEcoBankAccountRequest;
use Modules\Ecommerce\EcoBankAccount\Requests\GetEcoBankAccountListRequest;
use Modules\Ecommerce\EcoBankAccount\Requests\GetEcoBankAccountRequest;
use Modules\Ecommerce\EcoBankAccount\Requests\UpdateEcoBankAccountRequest;
use Modules\Ecommerce\EcoBankAccount\Services\EcoBankAccountCRUDService;
use Modules\Ecommerce\EcoBankAccount\Exports\EcoBankAccountExport;
use Modules\Ecommerce\EcoBankAccount\Requests\ExportEcoBankAccountRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class EcoBankAccountController extends Controller
{
    public function __construct(
        private EcoBankAccountCRUDService $ecoBankAccountService,
        private UpdateEcoBankAccountHandler $updateEcoBankAccountHandler,
        private DeleteEcoBankAccountHandler $deleteEcoBankAccountHandler,
    ) {
    }

    public function index(GetEcoBankAccountListRequest $request): JsonResponse
    {
        $list = $this->ecoBankAccountService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(EcoBankAccountPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoBankAccountRequest $request): JsonResponse
    {
        $item = $this->ecoBankAccountService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoBankAccountPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoBankAccountRequest $request): JsonResponse
    {
        $createdItem = $this->ecoBankAccountService->create($request->createCreateEcoBankAccountDTO());

        $presenter = new EcoBankAccountPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateEcoBankAccountRequest $request): JsonResponse
    {
        $id = Uuid::fromString($request->route('id'));
        $updatedItem = $this->ecoBankAccountService->update($id, $request->validated());

        $presenter = new EcoBankAccountPresenter($updatedItem);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteEcoBankAccountRequest $request): JsonResponse
    {
        $id = Uuid::fromString($request->route('id'));
        $this->ecoBankAccountService->delete($id);

        return Json::deleted();
    }

    /**
     * Export ecobankaccount to a file
     *
     * @param ExportEcoBankAccountRequest $request
     */
    public function export(ExportEcoBankAccountRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_bank_account.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new EcoBankAccountExport($this->ecoBankAccountService, $filters), $fileName);
    }
}
