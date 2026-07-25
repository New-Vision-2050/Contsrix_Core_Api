<?php

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Presenters\ViolationPresenter;
use Modules\Project\ProjectType\Services\ViolationService;

class ViolationController extends Controller
{
    public function __construct(private ViolationService $service) {}

    public function index(): JsonResponse
    {
        try {
            $violations = $this->service->listAll();

            return Json::items(
                $violations->map(fn ($v) => (new ViolationPresenter($v))->getData(true))->toArray()
            );
        } catch (\Exception $e) {
            return Json::error($e->getMessage(), method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500);
        }
    }
}
