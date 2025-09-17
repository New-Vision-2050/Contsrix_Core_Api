<?php

declare(strict_types=1);

namespace Modules\Shared\Installment\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\Installment\Handlers\DeleteInstallmentHandler;
use Modules\Shared\Installment\Handlers\UpdateInstallmentHandler;
use Modules\Shared\Installment\Presenters\InstallmentPresenter;
use Modules\Shared\Installment\Requests\CreateInstallmentRequest;
use Modules\Shared\Installment\Requests\DeleteInstallmentRequest;
use Modules\Shared\Installment\Requests\GetInstallmentListRequest;
use Modules\Shared\Installment\Requests\GetInstallmentRequest;
use Modules\Shared\Installment\Requests\UpdateInstallmentRequest;
use Modules\Shared\Installment\Services\InstallmentCRUDService;
use Modules\Shared\Installment\Exports\InstallmentExport;
use Modules\Shared\Installment\Requests\ExportInstallmentRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class InstallmentController extends Controller
{
    public function __construct(
        private InstallmentCRUDService $installmentService,
        private UpdateInstallmentHandler $updateInstallmentHandler,
        private DeleteInstallmentHandler $deleteInstallmentHandler,
    ) {
    }

    public function index(GetInstallmentListRequest $request): JsonResponse
    {
        $list = $this->installmentService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(InstallmentPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetInstallmentRequest $request): JsonResponse
    {
        $item = $this->installmentService->get(Uuid::fromString($request->route('id')));

        $presenter = new InstallmentPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateInstallmentRequest $request): JsonResponse
    {
        $createdItem = $this->installmentService->create($request->createCreateInstallmentDTO());

        $presenter = new InstallmentPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateInstallmentRequest $request): JsonResponse
    {
        $command = $request->createUpdateInstallmentCommand();
        $this->updateInstallmentHandler->handle($command);

        $item = $this->installmentService->get($command->getId());

        $presenter = new InstallmentPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteInstallmentRequest $request): JsonResponse
    {
        $this->deleteInstallmentHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export installment to a file
     *
     * @param ExportInstallmentRequest $request
     */
    public function export(ExportInstallmentRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'installment.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new InstallmentExport($this->installmentService, $filters), $fileName);
    }
}
