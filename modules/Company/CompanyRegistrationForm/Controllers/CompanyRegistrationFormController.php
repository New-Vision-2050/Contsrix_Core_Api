<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationForm\Controllers;

use BasePackage\Shared\Facade\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Company\CompanyRegistrationForm\Handlers\DeleteCompanyRegistrationFormHandler;
use Modules\Company\CompanyRegistrationForm\Handlers\UpdateCompanyRegistrationFormHandler;
use Modules\Company\CompanyRegistrationForm\Presenters\CompanyRegistrationFormPresenter;
use Modules\Company\CompanyRegistrationForm\Requests\CreateCompanyRegistrationFormRequest;
use Modules\Company\CompanyRegistrationForm\Requests\DeleteCompanyRegistrationFormRequest;
use Modules\Company\CompanyRegistrationForm\Requests\GetCompanyRegistrationFormListRequest;
use Modules\Company\CompanyRegistrationForm\Requests\GetCompanyRegistrationFormRequest;
use Modules\Company\CompanyRegistrationForm\Requests\UpdateCompanyRegistrationFormRequest;
use Modules\Company\CompanyRegistrationForm\Services\CompanyRegistrationFormCRUDService;
use Ramsey\Uuid\Uuid;

class CompanyRegistrationFormController extends Controller
{
    public function __construct(
        private CompanyRegistrationFormCRUDService $companyRegistrationFormService,
        private UpdateCompanyRegistrationFormHandler $updateCompanyRegistrationFormHandler,
        private DeleteCompanyRegistrationFormHandler $deleteCompanyRegistrationFormHandler,
    ) {
    }

    public function index(GetCompanyRegistrationFormListRequest $request): JsonResponse
    {
        $list = $this->companyRegistrationFormService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::buildItems(null,['company_registration_forms' => CompanyRegistrationFormPresenter::collection($list['data']),'pagination' => $list['pagination']]);
    }

    public function show(GetCompanyRegistrationFormRequest $request): JsonResponse
    {
        $item = $this->companyRegistrationFormService->get(Uuid::fromString($request->route('id')));

        $presenter = new CompanyRegistrationFormPresenter($item);

        return Json::buildItems('company_registration_form', $presenter->getData());
    }

    public function store(CreateCompanyRegistrationFormRequest $request): JsonResponse
    {
        $createdItem = $this->companyRegistrationFormService->create($request->createCreateCompanyRegistrationFormDTO());

        $presenter = new CompanyRegistrationFormPresenter($createdItem);

        return Json::buildItems('company_registration_form', $presenter->getData());
    }

    public function update(UpdateCompanyRegistrationFormRequest $request): JsonResponse
    {
        $command = $request->createUpdateCompanyRegistrationFormCommand();
        $this->updateCompanyRegistrationFormHandler->handle($command);

        $item = $this->companyRegistrationFormService->get($command->getId());

        $presenter = new CompanyRegistrationFormPresenter($item);

        return Json::buildItems('company_registration_form', $presenter->getData());
    }

    public function delete(DeleteCompanyRegistrationFormRequest $request): JsonResponse
    {
        $this->deleteCompanyRegistrationFormHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
