<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectManagement\Presenters\ProjectRequirementPresenter;
use Modules\Project\ProjectManagement\Presenters\ProjectRequirementSubmissionPresenter;
use Modules\Project\ProjectManagement\Requests\CreateProjectRequirementRequest;
use Modules\Project\ProjectManagement\Requests\CreateProjectRequirementSubmissionRequest;
use Modules\Project\ProjectManagement\Requests\GetProjectRequirementListRequest;
use Modules\Project\ProjectManagement\Requests\UpdateProjectRequirementRequest;
use Modules\Project\ProjectManagement\Services\ProjectRequirementService;
use Modules\Project\ProjectManagement\Services\ProjectRequirementSubmissionService;

class ProjectRequirementController extends Controller
{
    public function __construct(
        private readonly ProjectRequirementService $service,
        private readonly ProjectRequirementSubmissionService $submissionService,
    ) {}

    public function index(GetProjectRequirementListRequest $request, string $project): JsonResponse
    {
        $list = $this->service->list(
            projectId: $project,
            filters: $request->filters(),
            page: $request->page(),
            perPage: $request->perPage(),
        );

        return Json::items(
            ProjectRequirementPresenter::collection($list['data']),
            extraItems: ['summary' => $list['summary']],
            paginationSettings: $list['pagination'],
        );
    }

    public function store(CreateProjectRequirementRequest $request, string $project): JsonResponse
    {
        $created = $this->service->createMany($project, $request->rows());

        if ($created->count() === 1) {
            return Json::item((new ProjectRequirementPresenter($created->first()))->getData());
        }

        return Json::items(ProjectRequirementPresenter::collection($created));
    }

    public function show(string $project, string $requirement): JsonResponse
    {
        $item = $this->service->get($project, $requirement);

        return Json::item((new ProjectRequirementPresenter($item))->getData());
    }

    public function update(
        UpdateProjectRequirementRequest $request,
        string $project,
        string $requirement
    ): JsonResponse {
        $item = $this->service->update($project, $requirement, $request->validatedData());

        return Json::item((new ProjectRequirementPresenter($item))->getData());
    }

    public function destroy(string $project, string $requirement): JsonResponse
    {
        $this->service->delete($project, $requirement);

        return Json::deleted();
    }

    public function storeSubmission(
        CreateProjectRequirementSubmissionRequest $request,
        string $project,
        string $requirement
    ): JsonResponse {
        $submission = $this->submissionService->create($project, $requirement, $request->validatedData());

        return Json::item((new ProjectRequirementSubmissionPresenter($submission))->getData());
    }

    public function submissions(string $project, string $requirement): JsonResponse
    {
        $submissions = $this->submissionService->list($project, $requirement);

        return Json::items(ProjectRequirementSubmissionPresenter::collection($submissions));
    }
}
