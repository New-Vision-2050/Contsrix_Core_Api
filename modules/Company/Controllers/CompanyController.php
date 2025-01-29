<?php

declare(strict_types=1);

namespace Modules\Company\Controllers;

use BasePackage\Shared\Facade\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Company\Handlers\DeleteCompanyHandler;
use Modules\Company\Handlers\UpdateCompanyHandler;
use Modules\Company\Presenters\CompanyPresenter;
use Modules\Company\Requests\CreateCompanyRequest;
use Modules\Company\Requests\DeleteCompanyRequest;
use Modules\Company\Requests\GetCompanyListRequest;
use Modules\Company\Requests\GetCompanyRequest;
use Modules\Company\Requests\UpdateCompanyRequest;
use Modules\Company\Requests\ValidateCompanyRequest;
use Modules\Company\Services\CompanyCRUDService;
use Modules\Company\Services\CompanyValidateService;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function __construct(
        private CompanyCRUDService $companyService,
        private UpdateCompanyHandler $updateCompanyHandler,
        private DeleteCompanyHandler $deleteCompanyHandler,
        private CompanyValidateService $validateCompanyService
    ) {
    }

    public function index(GetCompanyListRequest $request): JsonResponse
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

    public function update(UpdateCompanyRequest $request): JsonResponse
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

}
