<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBankAccount\Controllers\Dashboard;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoBankAccount\Handlers\Dashboard\DeleteEcoBankAccountDashboardHandler;
use Modules\Ecommerce\EcoBankAccount\Handlers\Dashboard\UpdateEcoBankAccountDashboardHandler;
use Modules\Ecommerce\EcoBankAccount\Presenters\Dashboard\EcoBankAccountDashboardPresenter;
use Modules\Ecommerce\EcoBankAccount\Requests\Dashboard\CreateEcoBankAccountDashboardRequest;
use Modules\Ecommerce\EcoBankAccount\Requests\Dashboard\DeleteEcoBankAccountDashboardRequest;
use Modules\Ecommerce\EcoBankAccount\Requests\Dashboard\GetEcoBankAccountListDashboardRequest;
use Modules\Ecommerce\EcoBankAccount\Requests\Dashboard\GetEcoBankAccountDashboardRequest;
use Modules\Ecommerce\EcoBankAccount\Requests\Dashboard\UpdateEcoBankAccountDashboardRequest;
use Modules\Ecommerce\EcoBankAccount\Services\Dashboard\EcoBankAccountCRUDService;
use Modules\Ecommerce\EcoBankAccount\Exports\Dashboard\EcoBankAccountDashboardExport;
use Modules\Ecommerce\EcoBankAccount\Requests\Dashboard\ExportEcoBankAccountDashboardRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class EcoBankAccountDashboardController extends Controller
{
    public function __construct(
        private EcoBankAccountCRUDService $ecoBankAccountService,
        private UpdateEcoBankAccountDashboardHandler $updateEcoBankAccountHandler,
        private DeleteEcoBankAccountDashboardHandler $deleteEcoBankAccountHandler,
    ) {
    }

    public function index(GetEcoBankAccountListDashboardRequest $request): JsonResponse
    {
        $list = $this->ecoBankAccountService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(EcoBankAccountDashboardPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoBankAccountDashboardRequest $request): JsonResponse
    {
        $item = $this->ecoBankAccountService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoBankAccountDashboardPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoBankAccountDashboardRequest $request): JsonResponse
    {
        $createdItem = $this->ecoBankAccountService->create($request->createCreateEcoBankAccountDTO());

        $presenter = new EcoBankAccountDashboardPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateEcoBankAccountDashboardRequest $request): JsonResponse
    {
        $id = Uuid::fromString($request->route('id'));
        $updatedItem = $this->ecoBankAccountService->update($id, $request->validated());

        $presenter = new EcoBankAccountDashboardPresenter($updatedItem);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteEcoBankAccountDashboardRequest $request): JsonResponse
    {
        $id = Uuid::fromString($request->route('id'));
        $this->ecoBankAccountService->delete($id);

        return Json::deleted();
    }

    /**
     * Export ecobankaccount to a file
     *
     * @param ExportEcoBankAccountDashboardRequest $request
     */
    public function export(ExportEcoBankAccountDashboardRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_bank_account.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new EcoBankAccountDashboardExport($this->ecoBankAccountService, $filters), $fileName);
    }
}
