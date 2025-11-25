<?php

declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Attendance\Requests\GetUserConstraintRequest;
use Modules\Attendance\Services\UserAttendanceService;
use Ramsey\Uuid\Uuid;

class UserAttendanceController extends Controller
{
    public function __construct(
        private UserAttendanceService $userAttendanceService
    ) {}

    /**
     * Get current user's constraint for today
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyConstraintForToday(Request $request): JsonResponse
    {
        try {
            $userId = (string) $request->user()->id;

            $result = $this->userAttendanceService->getUserConstraintForToday($userId);

            return Json::item($result, message: __('messages.attendance.user_constraint_today_retrieved'));
        } catch (\Exception $e) {
            return Json::error(
                $e->getMessage(),
                500
            );
        }
    }

    /**
     * Check if current user is clocked in
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkMyClockInStatus(Request $request): JsonResponse
    {
        try {
            $userId = (string) $request->user()->id;

            $result = $this->userAttendanceService->checkClockInStatus($userId);

            return Json::item($result, message: __('messages.attendance.clock_in_status_retrieved'));
        } catch (\Exception $e) {
            return Json::error(
                $e->getMessage(),
                500
            );
        }
    }
}

