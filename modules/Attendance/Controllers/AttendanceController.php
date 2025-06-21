<?php

declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Support\Facades\Auth;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Requests\ClockInRequest;
use Modules\Attendance\Requests\ClockOutRequest;
use Modules\Attendance\Requests\GetAttendanceRequest;
use Modules\Attendance\Requests\UpdateAttendanceRequest;
use Modules\Attendance\Requests\FilterAttendanceRequest;
use Modules\Attendance\Presenters\AttendancePresenter;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
        private AttendanceConstraintService $constraintService,
        private AttendancePresenter $attendancePresenter,
    ) {}

    /**
     * Clock in employee
     */
    public function clockIn(ClockInRequest $request): JsonResponse
    {
        $clockInDTO = $request->createClockInDTO();

        // Validate constraints before clocking in
        $user = auth()->user();
        $mockAttendance = new \Modules\Attendance\Models\Attendance([
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'clock_in_time' => now(),
        ]);

        $violations = $this->constraintService->validateAttendance($mockAttendance, $request->all());

        if (!empty($violations)) {
            return Json::error(
                description: 'Clock-in blocked due to constraint violations',
                data: ['violations' => $violations],
                httpStatus: 422
            );
        }

        $attendance = $this->attendanceService->clockIn($clockInDTO);

        // Process any violations that occurred during clock-in
        $actualViolations = $this->constraintService->validateAttendance($attendance, $request->all());
        if (!empty($actualViolations)) {
            $this->constraintService->processViolations($attendance, $actualViolations);
        }

        return Json::item(
            (new AttendancePresenter($attendance))->getData(),
            message: 'Successfully clocked in'
        );
    }

    /**
     * Clock out employee
     */
    public function clockOut(ClockOutRequest $request): JsonResponse
    {
        $clockOutDTO = $request->createClockOutDTO();
        $attendance = $this->attendanceService->clockOut($clockOutDTO);

        // Validate constraints after clocking out
        $violations = $this->constraintService->validateAttendance($attendance, $request->all());
        if (!empty($violations)) {
            $this->constraintService->processViolations($attendance, $violations);
        }

        return Json::item(
            (new AttendancePresenter($attendance))->getData(),
            message: 'Successfully clocked out'
        );
    }

    /**
     * Start break
     */
    public function startBreak(Request $request): JsonResponse
    {
        $attendance = $this->attendanceService->startBreak(
            $request->user()->id,
            $request->input('notes')
        );

        return Json::item(
            (new AttendancePresenter($attendance))->getData(),
            message: 'Break started successfully'
        );
    }

    /**
     * End break
     */
    public function endBreak(Request $request): JsonResponse
    {
        $attendance = $this->attendanceService->endBreak(
            $request->user()->id,
            $request->input('notes')
        );

        return Json::item(
            (new AttendancePresenter($attendance))->getData(),
            message: 'Break ended successfully'
        );
    }

    /**
     * Get current attendance status
     */
    public function getCurrentStatus(Request $request): JsonResponse
    {
        $attendance = $this->attendanceService->getCurrentAttendance($request->user()->id);

        if (!$attendance) {
            return Json::item(['status' => 'not_clocked_in'], message: 'No active attendance found');
        }

        return Json::item(
            (new AttendancePresenter($attendance))->getData(),
            message: 'Current attendance status retrieved'
        );
    }

    /**
     * Get attendance history with filtering and pagination
     */
    public function getHistory(GetAttendanceRequest $request): JsonResponse
    {
        $filterDTO = $request->createFilterAttendanceDTO(Auth::user()->company_id);

        $result = $this->attendanceService->getAttendanceHistory(
            $filterDTO->toArray(),
            $filterDTO->getPage(),
            $filterDTO->getPerPage() ?? 10
        );

        $presentedData = AttendancePresenter::collection($result['data']);

        if ($result['pagination']) {
            return Json::items(
                                    $presentedData,
                paginationSettings: $result['pagination'],
                message:            'Attendance history retrieved successfully'
            );
        }

        return Json::items($presentedData, message: 'Attendance history retrieved successfully');
    }

    /**
     * Get attendance summary
     */
    public function getSummary(Request $request): JsonResponse
    {
        $userId = $request->input('user_id', $request->user()->id);
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $summary = $this->attendanceService->getAttendanceSummary($userId, $startDate, $endDate);

        return Json::item($summary, message: 'Attendance summary retrieved successfully');
    }

    /**
     * Update attendance record (for HR/Admin)
     */
    public function update(UpdateAttendanceRequest $request, string $attendanceId): JsonResponse
    {
        $attendance = $this->attendanceService->updateAttendance($attendanceId, $request->validated());

        return Json::item(
            (new AttendancePresenter($attendance))->getData(),
            message: 'Attendance updated successfully'
        );
    }

    /**
     * Approve attendance record
     */
    public function approve(Request $request, string $attendanceId): JsonResponse
    {
        $attendance = $this->attendanceService->approveAttendance(
            $attendanceId,
            $request->user()->id,
            $request->input('notes')
        );

        return Json::item(
            (new AttendancePresenter($attendance))->getData(),
            message: 'Attendance approved successfully'
        );
    }

    /**
     * Reject attendance record
     */
    public function reject(Request $request, string $attendanceId): JsonResponse
    {
        $attendance = $this->attendanceService->rejectAttendance(
            $attendanceId,
            $request->user()->id,
            $request->input('reason', 'No reason provided')
        );

        return Json::item(
            (new AttendancePresenter($attendance))->getData(),
            message: 'Attendance rejected successfully'
        );
    }

    /**
     * Delete attendance record
     */
    public function destroy(string $attendanceId): JsonResponse
    {
        $this->attendanceService->deleteAttendance($attendanceId);

        return Json::success('Attendance deleted successfully');
    }

    /**
     * Get team attendance with filtering and pagination
     */
    public function getTeamAttendance(FilterAttendanceRequest $request): JsonResponse
    {
        $filterDTO = $request->createFilterAttendanceDTO(Auth::user()->company_id);

        $result = $this->attendanceService->getTeamAttendance(
            Auth::id(), // Pass the current user's ID as the supervisor ID
            $filterDTO->toArray(), // Pass filters as the second parameter
            $filterDTO->getPage(),
            $filterDTO->getPerPage() ?? 10
        );

        $presentedData = AttendancePresenter::collection($result['data']);

        if ($result['pagination']) {
            return Json::items(
                mainItems:            $presentedData,
                paginationSettings: $result['pagination'],
                message:            'Team attendance retrieved successfully'
            );
        }

        return Json::items($presentedData, message: 'Team attendance retrieved successfully');
    }

    /**
     * Display a listing of attendance records with filtering and pagination.
     */
    public function index(FilterAttendanceRequest $request): JsonResponse
    {
        $filterDTO = $request->createFilterAttendanceDTO(Auth::user()->company_id);

        $result = $this->attendanceService->getAttendanceList(
            $filterDTO->toArray(),
            $filterDTO->getPage(),
            $filterDTO->getPerPage() ?? 10
        );

        $presentedData = AttendancePresenter::collection($result['data']);

        if ($result['pagination']) {
            return Json::items(
                                    $presentedData,
                paginationSettings: $result['pagination'],
                message:            'Attendance list retrieved successfully'
            );
        }

        return Json::items($presentedData, message: 'Attendance list retrieved successfully');
    }

    /**
     * Get late arrivals with filtering and pagination
     */
    public function getLateArrivals(FilterAttendanceRequest $request): JsonResponse
    {
        $filterDTO = $request->createFilterAttendanceDTO(Auth::user()->company_id);

        $result = $this->attendanceService->getLateArrivals(
            $filterDTO->toArray(),
            $filterDTO->getPage(),
            $filterDTO->getPerPage() ?? 10
        );

        $presentedData = AttendancePresenter::collection($result['data']);

        if ($result['pagination']) {
            return Json::items(
                $presentedData,
                message: 'Late arrivals retrieved successfully',
                paginationSettings: $result['pagination']
            );
        }

        return Json::items($presentedData, message: 'Late arrivals retrieved successfully');
    }

    /**
     * Get early departures with filtering and pagination
     */
    public function getEarlyDepartures(FilterAttendanceRequest $request): JsonResponse
    {
        $filterDTO = $request->createFilterAttendanceDTO(Auth::user()->company_id);

        $result = $this->attendanceService->getEarlyDepartures(
            $filterDTO->toArray(),
            $filterDTO->getPage(),
            $filterDTO->getPerPage() ?? 10
        );

        $presentedData = AttendancePresenter::collection($result['data']);

        if ($result['pagination']) {
            return Json::items(
                $presentedData,
                message: 'Early departures retrieved successfully',
                paginationSettings: $result['pagination']
            );
        }

        return Json::items($presentedData, message: 'Early departures retrieved successfully');
    }

    /**
     * Get overtime records with filtering and pagination
     */
    public function getOvertimeRecords(FilterAttendanceRequest $request): JsonResponse
    {
        $filterDTO = $request->createFilterAttendanceDTO(Auth::user()->company_id);

        $result = $this->attendanceService->getOvertimeRecords(
            $filterDTO->toArray(),
            $filterDTO->getPage(),
            $filterDTO->getPerPage() ?? 10
        );

        $presentedData = AttendancePresenter::collection($result['data']);

        if ($result['pagination']) {
            return Json::items(
                                    $presentedData,
                paginationSettings: $result['pagination'],
                message:            'Overtime records retrieved successfully'
            );
        }

        return Json::items($presentedData, message: 'Overtime records retrieved successfully');
    }
}
