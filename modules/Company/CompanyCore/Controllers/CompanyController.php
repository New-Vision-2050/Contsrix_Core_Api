<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
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
use Modules\Company\CompanyCore\Requests\GetBrokerCompaniesRequest;
use Modules\Company\CompanyCore\Requests\GetClientCompaniesRequest;
use Modules\Company\CompanyCore\Requests\GetCompanyListRequest;
use Modules\Company\CompanyCore\Requests\GetCompanyRequest;
use Modules\Company\CompanyCore\Requests\UpdateCompanyRequest;
use Modules\Company\CompanyCore\Services\CompanyCRUDService;
use Modules\Company\CompanyCore\Services\CompanyValidateService;
use Modules\Company\CompanyCore\Services\CompanyValidatedService;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use Modules\Company\CompanyCore\Handlers\ActivateCompanyHandler;
use Modules\Company\CompanyCore\Requests\ActiveCompanyRequest;
use Modules\Company\CompanyCore\Services\CompanyWidgetService;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyPresenter;
use Modules\User\Repositories\UserRepository;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Cache;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;

/**
 * Class CompanyController
 * @package Modules\Company\CompanyCore\Controllers
 */
class CompanyController extends Controller
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    public function __construct(
        private CompanyCRUDService $companyService,
        private UpdateCompanyHandler $updateCompanyHandler,
        private DeleteCompanyHandler $deleteCompanyHandler,
        private CompanyValidateService $validateCompanyService,
        private CompanyValidatedService $validatedCompanyService,
        private CompanyWidgetService $companyWidgetService,
        private ActivateCompanyHandler $activateCompanyCommand,
        private UserRepository $userRepository
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

    public function getClientCompanies(GetClientCompaniesRequest $request): JsonResponse
    {
        $list = $this->companyService->getClientCompanies(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(CompanyPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function getBrokerCompanies(GetBrokerCompaniesRequest $request): JsonResponse
    {
        $list = $this->companyService->getBrokerCompanies(
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


        $this->companyWidgetService->clearWidgetCache();

        $presenter = new CompanyPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateCompanyRequest $request): JsonResponse
    {

        $command = $request->createUpdateCompanyCommand();
        $this->updateCompanyHandler->handle($command);

        // Clear cache for current company logged in
        $cacheKey = 'current_company_logged_in_' . $command->getId() . '_' . $request->branch_id;
        Cache::forget($cacheKey);

        $this->companyWidgetService->clearWidgetCache();

        $item = $this->companyService->get($command->getId());

        $presenter = new CompanyPresenter($item);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteCompanyRequest $request): JsonResponse
    {
        $this->deleteCompanyHandler->handle(Uuid::fromString($request->route('id')));

        // Clear widget cache when a company is deleted
        $this->companyWidgetService->clearWidgetCache();

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
    /**
     * Get company statistics widget with caching
     *
     * @return JsonResponse
     */
    public function widget(): JsonResponse
    {
        // Cache key for company widget statistics
        $cacheKey = 'company_widget_statistics-'.app()->getLocale();

        // Get data from cache or compute if not available
        $widgetData = Cache::remember($cacheKey, now()->addHours(1), function () {
            $presenter = $this->companyWidgetService->getCompanyStatistics();
            return $presenter->getData();
        });

        return Json::item($widgetData);
    }

    /**
     * Clear the widget cache
     */

    public function activate(ActiveCompanyRequest $request): JsonResponse
    {
        $command = $request->createActiveCompanyCommand();
        $this->activateCompanyCommand->handle($command);

        // Clear widget cache when a company is activated/deactivated
        $this->companyWidgetService->clearWidgetCache();

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
        if($request->has('identifier_code')) {
            $company = $this->companyService->getCompanyByIdentifierCode($request->identifier_code);
        }
        if(empty($company)) {
            $company = $this->companyService->getCompanyByHost($request->header('X-DOMAIN') ?? $request->getHost());
        }
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
     */
//    public function export(ExportCompaniesRequest $request)
//    {
//        $companyIds = $request->input('company_ids');
//        $csv = $this->companyService->export($companyIds);
//        $filename = 'companies_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
//
//        return response()->streamDownload(function () use ($csv) {
//            echo $csv;
//        }, $filename, [
//            'Content-Type' => 'text/csv',
//            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
//        ]);
//    }
    public function export(ExportCompaniesRequest $request)
    {
        $companyIds = $request->input('ids');
        $format = strtolower($request->input('format', 'xlsx'));

        if (!in_array($format, ['xlsx', 'csv'])) {
            return Json::error('Invalid format. Supported formats are: xlsx, csv', 400);
        }

        $export = $this->companyService->export($companyIds, $format);
        $filename = 'companies_export_' . now()->format('Y-m-d_H-i-s');

        return Excel::download($export, $filename . '.' . $format);
    }

    public function deleteLastCreated(): JsonResponse
    {
        $this->companyService->deleteLastCreated();

        // Clear widget cache when the last created company is deleted
        $this->companyWidgetService->clearWidgetCache();

        return Json::deleted();
    }
}
