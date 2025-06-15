<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared\JobType\Handlers\ChangeJobTypeStatusHandler;
use Modules\Shared\JobType\Handlers\DeleteJobTypeHandler;
use Modules\Shared\JobType\Handlers\UpdateJobTypeHandler;
use Modules\Shared\JobType\Presenters\JobTypePresenter;
use Modules\Shared\JobType\Presenters\JobTypeSimplePresenter;
use Modules\Shared\JobType\Requests\ChangeJobTypeStatusRequest;
use Modules\Shared\JobType\Requests\CreateJobTypeRequest;
use Modules\Shared\JobType\Requests\DeleteJobTypeRequest;
use Modules\Shared\JobType\Requests\ExportJobTypeRequest;
use Modules\Shared\JobType\Requests\GetJobTypeListRequest;
use Modules\Shared\JobType\Requests\GetJobTypeRequest;
use Modules\Shared\JobType\Requests\GetJobTypeSimpleListRequest;
use Modules\Shared\JobType\Requests\UpdateJobTypeRequest;
use Modules\Shared\JobType\Services\JobTypeCRUDService;
use Modules\Shared\JobType\Exports\JobTypeExport;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class JobTypeController extends Controller
{
    public function __construct(
        private JobTypeCRUDService $jobTypeService,
        private UpdateJobTypeHandler $updateJobTypeHandler,
        private DeleteJobTypeHandler $deleteJobTypeHandler,
        private ChangeJobTypeStatusHandler $changeJobTypeStatusHandler,
    ) {
    }

    public function index(GetJobTypeListRequest $request): JsonResponse
    {
        $list = $this->jobTypeService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(JobTypePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function listSimple(GetJobTypeSimpleListRequest $request): JsonResponse
    {
        $list = $this->jobTypeService->listAll();

        return Json::items(JobTypeSimplePresenter::collection($list));
    }

    public function show(GetJobTypeRequest $request): JsonResponse
    {
        $item = $this->jobTypeService->get(Uuid::fromString($request->route('id')));

        $presenter = new JobTypePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateJobTypeRequest $request)
    {
        $createdItem = $this->jobTypeService->create($request->createCreateJobTypeDTO());

        $presenter = new JobTypePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateJobTypeRequest $request): JsonResponse
    {
        $command = $request->createUpdateJobTypeCommand();
        $this->updateJobTypeHandler->handle($command);

        $item = $this->jobTypeService->get($command->getId());

        $presenter = new JobTypePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteJobTypeRequest $request): JsonResponse
    {
        $this->deleteJobTypeHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    public function changeStatus(ChangeJobTypeStatusRequest $request): JsonResponse
    {
        $command = $request->createChangeJobTypeStatusCommand();
        $this->changeJobTypeStatusHandler->handle($command);

        $item = $this->jobTypeService->get($command->getId());

        $presenter = new JobTypePresenter($item);

        return Json::item($presenter->getData());
    }

    /**
     * Export job types to a file
     *
     * @param ExportJobTypeRequest $request
     */
    public function export(ExportJobTypeRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'job_types.' . $format;

        $filters = $request->getFilters();

        return Excel::download(new JobTypeExport($this->jobTypeService, $filters), $fileName);
    }
}
