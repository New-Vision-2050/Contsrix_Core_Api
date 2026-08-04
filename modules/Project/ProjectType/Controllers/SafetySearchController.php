<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Presenters\SafetySearchPresenter;
use Modules\Project\ProjectType\Requests\SafetySearchRequest;
use Modules\Project\ProjectType\Services\SafetyService;
use Throwable;

class SafetySearchController extends Controller
{
    public function __construct(private SafetyService $service) {}

    public function search(SafetySearchRequest $request): JsonResponse
    {
        try {
            $result = $this->service->search($request->queryString());

            return Json::item(
                (new SafetySearchPresenter($result['type'], $result['item']))->getData()
            );
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    private function errorResponse(Throwable $e): JsonResponse
    {
        $httpStatus = method_exists($e, 'getStatusCode') ? (int) $e->getStatusCode() : 500;

        return Json::error($e->getMessage(), $httpStatus, null, [], $httpStatus);
    }
}
