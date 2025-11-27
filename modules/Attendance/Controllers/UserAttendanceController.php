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
use Modules\Attendance\Requests\GetUserConstraintRequest;
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
    public function getMyConstraintForToday(GetUserConstraintRequest $request): JsonResponse
    {
        try {
            $userId = (string) Auth::id();
            $date = $request->input('date');

            $result = $this->userAttendanceService->getUserConstraints($userId, $date);

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
    public function getUserAttendanceHistory(Request $request): JsonResponse
    {
        $userId = (string) Auth::id();

        $result = $this->attendanceService->getTeamAttendance(
            ['user_id' => $userId, 'company_id' => Auth::user()->company_id],
            (int) $request->input('page', 1),
            (int) $request->input('per_page', 10)
        );

        if ($result->isEmpty()) {
            return Json::items([], message: 'No attendance records found');
        }
        return Json::items(
    AttendanceTeamPresenter::collection($result->items()),
    [],
    200,
    [
        'total' => $result->total(),
        'per_page' => $result->perPage(),
        'current_page' => $result->currentPage(),
        'last_page' => $result->lastPage(),
        'result_count' =>$result->total(),
    ]
    );
    }
}

