<?php

declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Attendance\Requests\DecidePenaltyExceptionRequest;
use Modules\Attendance\Services\PenaltyExceptionService;

/**
 * Penalty day-exception (استثناء اليوم) — employee self-service.
 *
 * When the rule-based auto clock-out applies a penalty to a day, the employee
 * may waive it by spending one of their monthly exceptions (default 3).
 */
class PenaltyExceptionController
{
    public function __construct(
        private PenaltyExceptionService $penaltyExceptionService,
    ) {}

    /**
     * GET /api/v1/attendance/penalty-exceptions/current-month
     *
     * Mobile: shows the monthly quota (limit / used / remaining), the penalized
     * days still awaiting a decision (drive the "penalty applied" message shown
     * the next day), and the decisions already taken.
     */
    public function currentMonth(Request $request): JsonResponse
    {
        return Json::item($this->penaltyExceptionService->monthlyStatus($request->user()));
    }

    /**
     * POST /api/v1/attendance/penalty-exceptions/{attendanceId}
     * Body: { "action": "use_exception" | "accept_penalty" }
     *
     * use_exception  → spends one monthly exception; the day's penalty is waived
     *                  (clock_out_time restored to the expected clock-out).
     * accept_penalty → records the decision; the penalty stands.
     */
    public function decide(DecidePenaltyExceptionRequest $request, string $attendanceId): JsonResponse
    {
        return Json::item($this->penaltyExceptionService->decide(
            $request->user(),
            $attendanceId,
            (string) $request->input('action'),
        ));
    }
}
