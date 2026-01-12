<?php

declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Presenters\AttendanceTeamPresenter;
use Modules\Attendance\Presenters\UserAttendanceHistoryPresenter;
use Modules\Attendance\Requests\GetUserConstraintRequest;
use Modules\Attendance\Requests\GetUserAttendanceHistoryRequest;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Services\UserAttendanceService;
use Ramsey\Uuid\Uuid;

class UserAttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
        private UserAttendanceService $userAttendanceService
    ) {}

    /**
     * Get current user's constraint for today
     *
     * @param GetUserConstraintRequest $request
     * @return JsonResponse
     */
    /**
     * Get current user's constraint for a specific date (or today if no date provided)
     *
     * @param GetUserConstraintRequest $request
     * @return JsonResponse
     */
    public function getMyConstraintForToday(GetUserConstraintRequest $request): JsonResponse
    {
        try {
            $userId = (string) Auth::id();
            $date = $request->input('date'); // Optional: Y-m-d format, defaults to today if null

            $timezone = function_exists('getTimeZoneByRequest') ? (getTimeZoneByRequest() ?? config('app.timezone')) : config('app.timezone');
            $targetDate = $date ?? \Carbon\Carbon::now($timezone)->format('Y-m-d');

            $result = $this->userAttendanceService->getUserConstraints($userId, $targetDate);
            
            return Json::item($result, message: __('messages.attendance.user_constraint_today_retrieved'));
        } catch (AttendanceException $e) {
            return Json::error(
                $e->getMessage(),
                $e->getStatusCode()
            );
        } catch (ModelNotFoundException $e) {
            return Json::error(
                'Resource not found.',
                404
            );
        } catch (ValidationException $e) {
            return Json::error(
                $e->getMessage(),
                422
            );
        } catch (\InvalidArgumentException $e) {
            return Json::error(
                $e->getMessage(),
                400
            );
        } catch (\Exception $e) {
            return Json::error(
                'An unexpected error occurred. Please try again later.',
                500
            );
        }
    }

    /**
     * Get current user's clock-in status
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyClockInStatus(Request $request): JsonResponse
    {
        $userId = (string) Auth::id();

        $result = $this->userAttendanceService->checkClockInStatus($userId);

        return Json::item($result, message: __('messages.attendance.clock_in_status_retrieved'));
    }
    /**
     * Get user attendance history grouped by date with periods
     *
     * @param GetUserAttendanceHistoryRequest $request
     * @return JsonResponse
     */
    public function getUserAttendanceHistory(GetUserAttendanceHistoryRequest $request): JsonResponse
    {
        try {
            $userId = (string) Auth::id();
            $month = $request->input('month') ? (int) $request->input('month') : null;
            $year = $request->input('year') ? (int) $request->input('year') : null;
            $page = (int) $request->input('page', 1);
            $perPage = (int) $request->input('per_page', 10);

            $timezone = function_exists('getTimeZoneByRequest') ? (getTimeZoneByRequest() ?? config('app.timezone')) : config('app.timezone');
            $currentDate = \Carbon\Carbon::now($timezone)->format('Y-m-d');
            $userConstraints = $this->userAttendanceService->getUserConstraints($userId, $currentDate);
            $canClockIn = false;
            
            if (isset($userConstraints['work_rules']['all_work_periods'])) {
                foreach ($userConstraints['work_rules']['all_work_periods'] as $period) {
                    if ($period['can_clock_in'] ?? false) {
                        $canClockIn = true;
                        break;
                    }
                }
            }

            $result = $this->userAttendanceService->getUserAttendanceHistory(
                $userId,
                $month,
                $year,
                $page,
                $perPage
            );
            $presentedData = UserAttendanceHistoryPresenter::collection($result['data']);

            return Json::items(
                $presentedData,
                [],
                200,
                [
                    'page' => $result['pagination']['page'],
                    'next_page' => $result['pagination']['next_page'] ?? $result['pagination']['page'],
                    'last_page' => $result['pagination']['last_page'],
                    'result_count' => $result['pagination']['result_count'],
                    'can_clock_in' => $canClockIn,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return Json::error(
                'User not found.',
                404
            );
        } catch (\Exception $e) {
            return Json::error(
                'An unexpected error occurred. Please try again later.',
                500
            );
        }
    }
}

