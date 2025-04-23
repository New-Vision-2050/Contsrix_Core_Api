<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Controllers;

use BasePackage\Shared\Facade\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Company\CompanyCore\Requests\ExportCompaniesRequest;
use Modules\Company\CompanyCore\Handlers\DeleteCompanyHandler;
use Modules\Company\CompanyCore\Handlers\UpdateCompanyHandler;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Presenters\CompanyPresenter;
use Modules\Company\CompanyCore\Presenters\CompanyUnAuthPresenter;
use Modules\Company\CompanyCore\Requests\CreateCompanyRequest;
use Modules\Company\CompanyCore\Requests\DeleteCompanyRequest;
use Modules\Company\CompanyCore\Requests\GetCompanyListRequest;
use Modules\Company\CompanyCore\Requests\GetCompanyRequest;
use Modules\Company\CompanyCore\Requests\UpdateCompanyRequest;
use Modules\Company\CompanyCore\Services\CompanyCRUDService;
use Modules\Company\CompanyCore\Services\CompanyValidateService;
use Modules\Company\CompanyCore\Services\CompanyValidatedService;
use Modules\Shared\Media\Services\FileUploadService;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use Modules\Company\CompanyCore\Handlers\ActivateCompanyHandler;
use Modules\Company\CompanyCore\Presenters\CompanyWidgetPresenter;
use Modules\Company\CompanyCore\Requests\ActiveCompanyRequest;
use Modules\Company\CompanyCore\Services\CompanyWidgetService;
class CompanyController extends Controller
{
    public function __construct(
        private CompanyCRUDService $companyService,
        private UpdateCompanyHandler $updateCompanyHandler,
        private DeleteCompanyHandler $deleteCompanyHandler,
        private CompanyValidateService $validateCompanyService,
        private CompanyValidatedService $validatedCompanyService,
        private CompanyWidgetService $companyWidgetService,
        private ActivateCompanyHandler $activateCompanyCommand,
        // private TransformImgsService  $transformImgsService
    ) {
    }

    public function index(GetCompanyListRequest $request): JsonResponse
    {

        $list = $this->companyService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(CompanyPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetCompanyRequest $request): JsonResponse
    {
        $item = $this->companyService->get(Uuid::fromString($request->route('id')));

        $presenter = new CompanyPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateCompanyRequest $request)
    {
        $createdItem = $this->companyService->create($request->createCreateCompanyDTO());

        $presenter = new CompanyPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateCompanyRequest $request)//: JsonResponse
    {

        $command = $request->createUpdateCompanyCommand();
        $this->updateCompanyHandler->handle($command);

        $item = $this->companyService->get($command->getId());

        $presenter = new CompanyPresenter($item);

        return Json::item($presenter->getData());
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
        $presenter = $this->companyWidgetService->getCompanyStatistics();

        return Json::item($presenter->getData());

    }
    public function activate(ActiveCompanyRequest $request): JsonResponse
    {
        $command = $request->createActiveCompanyCommand();
        $this->activateCompanyCommand->handle($command);

        $item = $this->companyService->get($command->getId());

        $presenter = new CompanyPresenter($item);

        return Json::item($presenter->getData());
    }

    public function validated(Request $request): JsonResponse
    {

        $item = $this->validatedCompanyService->validate($request);

        return Json::item($item);
    }

    public function getCurrentCompanyLoggedIn()
    {

        try {
            $company = $this->companyService->getCurrentCompanyLoggedIn();
        } catch (\Exception $e) {
            return Json::error($e->getMessage(),$e->getCode());
        }
        return Json::item((new CompanyPresenter($company))->getData());
    }

    public function getCompanyByHost(Request $request)
    {
        $company = $this->companyService->getCompanyByHost($request->header('X-DOMAIN') ?? $request->getHost());
        return Json::item((new CompanyUnAuthPresenter($company))->getData());
    }

    /**
     * Get company by name
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getByName(Request $request): JsonResponse
    {
        try {
            $name = $request->query('name');
            
            if (!$name) {
                return Json::error('Company name is required', 400);
            }

            $company = $this->companyService->getByName($name);
            
            if (!$company) {
                return Json::error('Company not found', 404);
            }

            return Json::item((new CompanyPresenter($company))->getData());
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), 500);
        }
    }

    /**
     * Export companies data as CSV
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(ExportCompaniesRequest $request)
    {
        $companyIds = $request->input('company_ids');
        $format = strtolower($request->input('format', 'xlsx'));
        
        if (!in_array($format, ['xlsx', 'csv'])) {
            return Json::error('Invalid format. Supported formats are: xlsx, csv', 400);
        }

        $export = $this->companyService->export($companyIds, $format);
        $filename = 'companies_export_' . now()->format('Y-m-d_H-i-s');

        return Excel::download($export, $filename . '.' . $format);
    }
}
