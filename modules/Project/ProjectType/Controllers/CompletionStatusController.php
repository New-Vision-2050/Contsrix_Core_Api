<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\ProjectType\Presenters\CompletionPhaseStatusPresenter;
use Modules\Project\ProjectType\Presenters\CompletionStatusPresenter;
use Modules\Project\ProjectType\Services\CompletionStatusService;

class CompletionStatusController extends Controller
{
    public function __construct(private readonly CompletionStatusService $service)
    {
    }

    public function projectPhases(): JsonResponse
    {
        $items = $this->service->listProjectPhases();

        return Json::items(CompletionStatusPresenter::collection($items));
    }

    public function connectionPhases(): JsonResponse
    {
        $items = $this->service->listConnectionPhases();

        return Json::items(CompletionStatusPresenter::collection($items));
    }

    public function projectStatuses(Request $request): JsonResponse
    {
        $items = $this->service->listProjectStatuses($request->only('project_completion_phase_id'));

        return Json::items(CompletionPhaseStatusPresenter::collection($items));
    }

    public function connectionStatuses(Request $request): JsonResponse
    {
        $items = $this->service->listConnectionStatuses($request->only('connection_completion_phase_id'));

        return Json::items(CompletionPhaseStatusPresenter::collection($items));
    }
}
