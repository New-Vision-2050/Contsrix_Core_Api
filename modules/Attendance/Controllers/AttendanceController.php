<?php

declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use AWS\CRT\HTTP\Message;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Support\Facades\Auth;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Presenters\AttendanceUserPresenter;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Requests\ClockInRequest;
use Modules\Attendance\Requests\ClockOutRequest;
use Modules\Attendance\Requests\GetAttendanceRequest;
use Modules\Attendance\Requests\UpdateAttendanceRequest;
use Modules\Attendance\Requests\FilterAttendanceRequest;
use Modules\Attendance\Presenters\AttendancePresenter;
use Modules\Attendance\Presenters\AttendanceTeamPresenter;
use Modules\Attendance\Presenters\AttendanceBreakPresenter;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Requests\AttendanceRequest;
use Modules\Attendance\Requests\BreakRequest;
use Modules\Attendance\Services\MockAttendanceService;
use Modules\Company\CompanyCore\Models\Company;
use Ramsey\Uuid\Uuid;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Presenters\AppliedAttendanceConstraintPresenter;
use Modules\Attendance\Requests\ExportAttendanceRequest;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Attendance\Exports\AttendanceTeamExport;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
        private AttendanceConstraintService $constraintService,
        private MockAttendanceService $mockAttendanceService
    ) {}
    public function test(Request $request): JsonResponse
    {
            return Json::item( Attendance::withoutTenancy()->get());
    }

    /**
     * Clock in employee.
     * Validates work period and constraints, then persists attendance.
     */
    public function clockIn(ClockInRequest $request): JsonResponse
    {
        try {
            $clockInDTO = $request->createClockInDTO();
            $rawRequestData = $request->all();

            $violations = $this->mockAttendanceService->validateClockIn($clockInDTO, $rawRequestData);
            if (!empty($violations)) {
                return Json::error(
                    description: $violations[0]['message'] ?? 'Clock-in blocked due to constraint violations',
                    data: ['violations' => $violations],
                    httpStatus: 422
                );
            }

            $attendance = $this->mockAttendanceService->persistClockIn($clockInDTO, $rawRequestData);

            return Json::item(
                (new AttendancePresenter($attendance))->present(),
                message: 'Successfully clocked in.'
            );
        } catch (AttendanceException $e) {
            return Json::error($e->getMessage(), httpStatus: $e->getStatusCode());
        }
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
            // Process each violation individually
            foreach ($violations as $violationData) {
                if (isset($violationData['constraint_id'])) {
                    $constraint = AttendanceConstraint::find($violationData['constraint_id']);
                    if ($constraint) {
                        $this->constraintService->createViolation($attendance, $constraint, $violationData);
                    }
                }
            }
        }

        return Json::item(
            (new AttendancePresenter($attendance))->present(),
            message: 'Successfully clocked out'
        );
    }

    /**
     * Start break
     */
    public function startBreak(BreakRequest $request): JsonResponse
    {
        $attendance = $this->attendanceService->startBreak(
            $request->user()->id,
            $request->input('notes')
        );

        $presenter = new AttendancePresenter($attendance);

        return Json::item(
            $presenter->present(),
            message: 'Break started successfully'
        );
    }

    /**
     * End break
     */
    public function endBreak(BreakRequest $request): JsonResponse
    {
        $attendance = $this->attendanceService->endBreak(
            $request->user()->id,
            $request->input('notes')
        );

        // Validate break time limits
        $violationData = $this->constraintService->validateBreakEnd($attendance);

        if ($violationData) {
            // If a violation is found, create a record for it
            $constraint = AttendanceConstraint::find($violationData['constraint_id']);
            if ($constraint) {
                $this->constraintService->createViolation($attendance, $constraint, $violationData);
            }
        }

        $presenter = new AttendancePresenter($attendance);

        return Json::item(
            $presenter->present(),
            message: 'Break ended successfully'
        );
    }

    /**
     * Get attendance status information
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStatus(Request $request): JsonResponse
    {
        $attendanceId = $request->input('attendance_id');

        if (!$attendanceId) {
            // If no specific attendance ID is provided, return current attendance status
            $attendance = $this->attendanceService->getCurrentAttendance($request->user()->id);
        } else {
            // If an attendance ID is provided, return that specific attendance
            $attendance = $this->attendanceService->getAttendance(Uuid::fromString($attendanceId));
        }


        if (!$attendance) {
            return response()->json([
                'code' => 'SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT',
                'message' =>    'No active attendance found',
                'payload' => NULL
            ]);
        }

        $presenter = new AttendancePresenter($attendance);

        return Json::item($presenter->getData());
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
            (new AttendancePresenter($attendance))->present(),
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
            (int) $request->input('page',1),
            (int) $request->input('per_page',10)
        );

        // $presentedData = AttendancePresenter::collection($result['data']);

        // if ($result['pagination']) {
        //     return Json::items(
        //                             $presentedData,
        //         paginationSettings: $result['pagination'],
        //         message:            'Attendance history retrieved successfully'
        //     );
        // }

        return Json::items($result['data'], paginationSettings: $result['pagination'], message: 'Attendance history retrieved successfully');
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
    public function appliedAttendanceConstraint(string $id)//: JsonResponse
    {
        $constraint = $this->attendanceService->getAttendance(Uuid::fromString($id));
        // return  $constraint->appliedAttendanceConstraint;
        $constraintPresenter =(new AppliedAttendanceConstraintPresenter($constraint))->getData();
        return Json::item($constraintPresenter, message: 'Constraint retrieved successfully');
    }
    /**
     * Update attendance record (for HR/Admin)
     */
    public function update(UpdateAttendanceRequest $request, string $attendanceId): JsonResponse
    {
        $attendance = $this->attendanceService->updateAttendance($attendanceId, $request->validated());

        return Json::item(
            (new AttendancePresenter($attendance))->present(),
            message: 'Attendance updated successfully'
        );
    }

    /**
     * Approve attendance record
     */
    public function approve(Request $request): JsonResponse
    {
        $attendance = $this->attendanceService->approveAttendance(
            Uuid::fromString($request->route('attendanceId')),
            $request->user()->id,
            $request->input('notes')
        );

        return Json::item(
            (new AttendancePresenter($attendance))->present(),
            message: 'Attendance approved successfully'
        );
    }

    /**
     * Reject attendance record
     */
    public function reject(Request $request): JsonResponse
    {
        $attendance = $this->attendanceService->rejectAttendance(
            Uuid::fromString($request->route('attendanceId')),
            $request->user()->id,
            $request->input('reason', 'No reason provided')
        );

        return Json::item(
            (new AttendancePresenter($attendance))->present(),
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
    /**
     * Users currently clocked in (not clocked out) for the company.
     */
    public function getOpenAttendances(FilterAttendanceRequest $request): JsonResponse
    {
        $filterDTO = $request->createFilterAttendanceDTO(Auth::user()->company_id);

        $result = $this->attendanceService->getOpenAttendances(
            $filterDTO->toArray(),
            (int) $request->input('page', 1),
            (int) $request->input('per_page', 10)
        );

        if ($result->isEmpty()) {
            return Json::items([], message: 'No open attendance records found');
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
                'result_count' => $result->total(),
            ]
        );
    }

    public function getTeamAttendance(FilterAttendanceRequest $request)//: JsonResponse
    {
        $filterDTO = $request->createFilterAttendanceDTO(Auth::user()->company_id);

        $result = $this->attendanceService->getTeamAttendance(
            $filterDTO->toArray(),
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
        ]);
    }
        public function getUserAttendance(FilterAttendanceRequest $request)//: JsonResponse
    {
        $filterDTO = $request->createFilterAttendanceDTO(Auth::user()->company_id);

        $result = $this->attendanceService->getTeamAttendance(
            $filterDTO->toArray(),
            (int) $request->input('page', 1),
            (int) $request->input('per_page', 10),
            auth()->user()->id
        );
        if ($result->isEmpty()) {
            return Json::items([], message: 'No attendance records found');
        }
        return Json::items(
    AttendanceUserPresenter::collection($result->items()),
    [],
    200,
    [
            'total' => $result->total(),
            'per_page' => $result->perPage(),
            'current_page' => $result->currentPage(),
            'last_page' => $result->lastPage(),
            'result_count' =>$result->total(),
        ]);
    }
    public function teamAttendance(AttendanceRequest $request)//: JsonResponse
    {
        $attendance = $this->attendanceService->getAttendance(Uuid::fromString($request->route('attendance')));

        return Json::item((new AttendancePresenter($attendance))->present());
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
   public function exportTeamAttendance(ExportAttendanceRequest $request)
    {
        $format = $request->get('format', 'xlsx'); // Default to xlsx if not specified
        if (!in_array($format, ['xlsx', 'csv'])) {
            return Json::error('Invalid format. Supported formats are: xlsx, csv', 400);
        }

        $filterDTO = $request->createFilterAttendanceDTO(Auth::user()->company_id);
        $filters = $filterDTO->toArray();

        $fileName = 'team_attendance_' . now()->format('Y-m-d_H-i-s') . '.' . $format;

        // Pass the service instance and the filters to the export class
        return Excel::download(new AttendanceTeamExport($this->attendanceService, $filters), $fileName);
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

    /**
     * Get breaks for a specific attendance record
     */
    public function getBreaks(Request $request, string $attendanceId): JsonResponse
    {
        $breaks = $this->attendanceService->getBreaks($attendanceId);

        return Json::collection(
            $breaks,
            message: 'Attendance breaks retrieved successfully'
        );
    }
}
