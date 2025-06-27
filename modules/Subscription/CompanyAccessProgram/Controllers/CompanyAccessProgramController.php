<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Subscription\CompanyAccessProgram\Handlers\DeleteCompanyAccessProgramHandler;
use Modules\Subscription\CompanyAccessProgram\Handlers\UpdateCompanyAccessProgramHandler;
use Modules\Subscription\CompanyAccessProgram\Presenters\CompanyAccessProgramPresenter;
use Modules\Subscription\CompanyAccessProgram\Requests\CreateCompanyAccessProgramRequest;
use Modules\Subscription\CompanyAccessProgram\Requests\DeleteCompanyAccessProgramRequest;
use Modules\Subscription\CompanyAccessProgram\Requests\GetCompanyAccessProgramListRequest;
use Modules\Subscription\CompanyAccessProgram\Requests\GetCompanyAccessProgramRequest;
use Modules\Subscription\CompanyAccessProgram\Requests\UpdateCompanyAccessProgramRequest;
use Modules\Subscription\CompanyAccessProgram\Services\CompanyAccessProgramCRUDService;
use Ramsey\Uuid\Uuid;

class CompanyAccessProgramController extends Controller
{
    public function __construct(
        private CompanyAccessProgramCRUDService $companyAccessProgramService,
        private UpdateCompanyAccessProgramHandler $updateCompanyAccessProgramHandler,
        private DeleteCompanyAccessProgramHandler $deleteCompanyAccessProgramHandler,
    ) {
    }

    public function index(GetCompanyAccessProgramListRequest $request): JsonResponse
    {
        $list = $this->companyAccessProgramService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(CompanyAccessProgramPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetCompanyAccessProgramRequest $request): JsonResponse
    {
        $item = $this->companyAccessProgramService->get(Uuid::fromString($request->route('id')));

        $presenter = new CompanyAccessProgramPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateCompanyAccessProgramRequest $request): JsonResponse
    {
        $createdItem = $this->companyAccessProgramService->create($request->createCreateCompanyAccessProgramDTO());

        $presenter = new CompanyAccessProgramPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateCompanyAccessProgramRequest $request): JsonResponse
    {
        $command = $request->createUpdateCompanyAccessProgramCommand();
        $this->updateCompanyAccessProgramHandler->handle($command);

        $item = $this->companyAccessProgramService->get($command->getId());

        $presenter = new CompanyAccessProgramPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteCompanyAccessProgramRequest $request): JsonResponse
    {
        $this->deleteCompanyAccessProgramHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}
