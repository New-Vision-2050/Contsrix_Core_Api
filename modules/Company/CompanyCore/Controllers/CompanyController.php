<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Controllers;

use BasePackage\Shared\Facade\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Company\CompanyCore\Handlers\DeleteCompanyHandler;
use Modules\Company\CompanyCore\Handlers\UpdateCompanyHandler;
use Modules\Company\CompanyCore\Presenters\CompanyPresenter;
use Modules\Company\CompanyCore\Requests\CreateCompanyRequest;
use Modules\Company\CompanyCore\Requests\DeleteCompanyRequest;
use Modules\Company\CompanyCore\Requests\GetCompanyListRequest;
use Modules\Company\CompanyCore\Requests\GetCompanyRequest;
use Modules\Company\CompanyCore\Requests\UpdateCompanyRequest;
use Modules\Company\CompanyCore\Services\CompanyCRUDService;
use Modules\Company\CompanyCore\Services\CompanyValidateService;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use Modules\Company\CompanyCore\Handlers\ActivateCompanyHandler;
use Modules\Company\CompanyCore\Presenters\CompanyWidgetPresenter;
use Modules\Company\CompanyCore\Requests\ActiveCompanyRequest;
use Modules\Company\CompanyCore\Services\CompanyWidgetService;
use Illuminate\Support\Facades\Storage;
class CompanyController extends Controller
{
    public function __construct(
        private CompanyCRUDService $companyService,
        private UpdateCompanyHandler $updateCompanyHandler,
        private DeleteCompanyHandler $deleteCompanyHandler,
        private CompanyValidateService $validateCompanyService,
        private CompanyWidgetService $companyWidgetService,
        private ActivateCompanyHandler $activateCompanyCommand,
        private FileUploadService  $fileUploadService
    ) {
    }

    public function index(GetCompanyListRequest $request)//: JsonResponse
    {

        $list = $this->companyService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::buildItems(null,['companies' => CompanyPresenter::collection($list['data']),'pagination' => $list['pagination']]);
    }

    public function show(GetCompanyRequest $request): JsonResponse
    {
        $item = $this->companyService->get(Uuid::fromString($request->route('id')));

        $presenter = new CompanyPresenter($item);

        return Json::buildItems('company', $presenter->getData());
    }

    public function store(CreateCompanyRequest $request): JsonResponse
    {
        $createdItem = $this->companyService->create($request->createCreateCompanyDTO());

        $presenter = new CompanyPresenter($createdItem);

        return Json::buildItems('company', $presenter->getData());
    }

    public function update(UpdateCompanyRequest $request)//: JsonResponse
    {

        $command = $request->createUpdateCompanyCommand();
        $this->updateCompanyHandler->handle($command);

        $item = $this->companyService->get($command->getId());

        $presenter = new CompanyPresenter($item);

        return Json::buildItems('company', $presenter->getData());
    }

    public function delete(DeleteCompanyRequest $request): JsonResponse
    {
        $this->deleteCompanyHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
    public function validate(Request $request)//: JsonResponse
    {
        $validationResult = $this->validateCompanyService->validate($request);

        return response()->json([
            'status' => 'success',
            'data' => $validationResult,
        ]);
    }
    public function widget(): JsonResponse
    {
        $total = $this->companyWidgetService->total();
        $active = $this->companyWidgetService->active();
        $completeData = $this->companyWidgetService->completeData();
        $dataActivate = $this->companyWidgetService->dataActivate();

        $totalCalculate = $this->companyWidgetService->totalCalculate();
        $activeCalculate = $this->companyWidgetService->activeCalculate();
        $completeDataCalculate = $this->companyWidgetService->completeDataCalculate();
        $dataActivateCalculate = $this->companyWidgetService->dataActivateCalculate();

        $presenter = new CompanyWidgetPresenter($total,$active,$completeData,$dataActivate,$totalCalculate,$activeCalculate,$completeDataCalculate,$dataActivateCalculate);

        return Json::buildItems('company', $presenter->getData());
    }
    public function activate(ActiveCompanyRequest $request): JsonResponse
    {
        $command = $request->createActiveCompanyCommand();
        $this->activateCompanyCommand->handle($command);

        $item = $this->companyService->get($command->getId());

        $presenter = new CompanyPresenter($item);

        return Json::buildItems('company', $presenter->getData());
    }
    public function test(Request $request)
    {
        return response()->json($this->fileUploadService->uploadToMinIO($request));
    }
}
