<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Facade\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectManagement\Actions\TestPCloudConnectionAction;
use Modules\Project\ProjectManagement\Exceptions\PCloudConfigurationException;
use RuntimeException;

class ProjectPCloudTestController extends Controller
{
    public function __invoke(TestPCloudConnectionAction $testPCloudConnection): JsonResponse
    {
        try {
            return Json::item(
                $testPCloudConnection->execute(),
                message: 'pCloud server test completed',
            );
        } catch (PCloudConfigurationException $exception) {
            return Json::error($exception->getMessage(), httpStatus: 422);
        } catch (RuntimeException $exception) {
            return Json::error($exception->getMessage(), httpStatus: 502);
        }
    }
}
