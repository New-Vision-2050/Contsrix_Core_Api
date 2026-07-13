<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Presenters\ProjectOrderPermitPresenter;
use Modules\Project\ProjectType\Requests\CreateProjectOrderPermitRequest;
use Modules\Project\ProjectType\Services\ProjectOrderPermitService;

class ProjectOrderPermitController extends Controller
{
    public function __construct(private readonly ProjectOrderPermitService $service)
    {
    }

    public function store(CreateProjectOrderPermitRequest $request): JsonResponse
    {
        $items = $this->service->createMany($request->validated());

        return Json::items(
            array_map(
                static fn ($item) => (new ProjectOrderPermitPresenter($item))->getData(),
                $items
            )
        );
    }
}
