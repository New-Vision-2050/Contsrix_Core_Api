<?php

declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Attendance\Services\DashboardOverviewService;
use Modules\User\Models\User;

class DashboardOverviewController extends Controller
{
    public function __construct(
        private readonly DashboardOverviewService $overviewService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return Json::error('Unauthorized.', httpStatus: 401);
        }

        return Json::item(
            $this->overviewService->overview($user),
            message: 'Dashboard overview retrieved successfully',
        );
    }
}
