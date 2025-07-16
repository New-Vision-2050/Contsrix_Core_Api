<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Controllers;

use Ramsey\Uuid\Uuid;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Modules\Subscription\CompanyAccessProgram\Requests\GetCompanyAccessProgramRequest;
use Modules\Subscription\CompanyAccessProgram\Presenters\CompanyAccessProgramPresenter;
use Modules\Subscription\CompanyAccessProgram\Services\CompanyAccessProgramCRUDService;
use Modules\Subscription\CompanyAccessProgram\Handlers\DeleteCompanyAccessProgramHandler;
use Modules\Subscription\CompanyAccessProgram\Handlers\UpdateCompanyAccessProgramHandler;
use Modules\Subscription\CompanyAccessProgram\Requests\CreateCompanyAccessProgramRequest;
use Modules\Subscription\CompanyAccessProgram\Requests\DeleteCompanyAccessProgramRequest;
use Modules\Subscription\CompanyAccessProgram\Requests\UpdateCompanyAccessProgramRequest;
use Modules\Subscription\CompanyAccessProgram\Requests\GetCompanyAccessProgramListRequest;
use Modules\Subscription\CompanyAccessProgram\Handlers\UpdateCompanyAccessProgramStatusHandler;
use Modules\Subscription\CompanyAccessProgram\Requests\UpdateCompanyAccessProgramStatusRequest;
use Modules\Subscription\CompanyAccessProgram\Presenters\CompanyAccessProgramPackageFormMetaPresenter;
use Modules\Subscription\CompanyAccessProgram\Requests\ExportCompanyAccessProgramRequest;
use Modules\Subscription\CompanyAccessProgram\Exports\CompanyAccessProgramExport;
use Maatwebsite\Excel\Facades\Excel;

class CompanyAccessProgramController extends Controller
{
    public function __construct(
        private CompanyAccessProgramCRUDService $companyAccessProgramService,
        private UpdateCompanyAccessProgramHandler $updateCompanyAccessProgramHandler,
        private UpdateCompanyAccessProgramStatusHandler $updateCompanyAccessProgramStatusHandler,
        private DeleteCompanyAccessProgramHandler $deleteCompanyAccessProgramHandler,
    ) {
    }

    public function index(GetCompanyAccessProgramListRequest $request): JsonResponse
    {
        $filters = [];

        if($request->has('status')) {
            $filters['is_active'] = $request->boolean('status');
        }

        if($request->has('name')) {
            $filters['name'] = $request->get('name');
        }

        if($request->has('company_field_id')) {
            $filters['company_field_id'] = $request->input('company_field_id');
        }

        $list = $this->companyAccessProgramService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10),
            $filters
        );

        return Json::items(CompanyAccessProgramPresenter::collection($list['data'], $this->companyAccessProgramService), paginationSettings: $list['pagination']);
    }

    public function counts(GetCompanyAccessProgramListRequest $request): JsonResponse
    {
        $counts = $this->companyAccessProgramService->counts();

        return Json::item($counts);
    }

    public function show(GetCompanyAccessProgramRequest $request): JsonResponse
    {
        $item = $this->companyAccessProgramService->get(Uuid::fromString($request->route('id')));

        $presenter = new CompanyAccessProgramPresenter($item, $this->companyAccessProgramService);

        return Json::item($presenter->getData());
    }

    public function store(CreateCompanyAccessProgramRequest $request): JsonResponse
    {
        $createdItem = $this->companyAccessProgramService->create($request->createCreateCompanyAccessProgramDTO());

        $presenter = new CompanyAccessProgramPresenter($createdItem, $this->companyAccessProgramService);

        return Json::item($presenter->getData());
    }

    public function update(UpdateCompanyAccessProgramRequest $request): JsonResponse
    {
        $command = $request->createUpdateCompanyAccessProgramCommand();
        $this->updateCompanyAccessProgramHandler->handle($command);

        $item = $this->companyAccessProgramService->get($command->getId());

        $presenter = new CompanyAccessProgramPresenter($item, $this->companyAccessProgramService);

        return Json::item($presenter->getData());
    }

    public function updateStatus(UpdateCompanyAccessProgramStatusRequest $request): JsonResponse
    {
        $command = $request->createUpdateCompanyAccessProgramStatusCommand();
        $this->updateCompanyAccessProgramStatusHandler->handle($command);

        $item = $this->companyAccessProgramService->get($command->getId());

        $presenter = new CompanyAccessProgramPresenter($item, $this->companyAccessProgramService);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteCompanyAccessProgramRequest $request): JsonResponse
    {
        $this->deleteCompanyAccessProgramHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    public function getPackageFormMeta(): JsonResponse
    {
        $item = $this->companyAccessProgramService->getPackageFormMeta(request()->route('id'));

        $presenter = new CompanyAccessProgramPackageFormMetaPresenter($item);

        return Json::item($presenter->getData());
    }

    /**
     * Export company access programs to a file
     *
     * @param ExportCompanyAccessProgramRequest $request
     */
    public function export(ExportCompanyAccessProgramRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'company_access_programs.' . $format;

        $filters = $request->getFilters();

        return Excel::download(new CompanyAccessProgramExport($this->companyAccessProgramService, $filters), $fileName);
    }
}
