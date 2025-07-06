<?php

declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Attendance\Requests\TrackLocationRequest;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Presenters\LiveTrackingPresenter;
use Modules\Attendance\Requests\LiveTrackingRequest;
use Modules\Attendance\Services\LocationTrackingService;

class LocationTrackingController
{
    // This constructor is correct.
    public function __construct(private LocationTrackingService $trackingService)
    {
    }

    public function getLiveTrackingData(LiveTrackingRequest $request)//: JsonResponse
    {
        $activeAttendances = $this->trackingService->getTodaysActiveAttendance(
            $request->validated()
        );

        $presentedData = collect($activeAttendances)->map(function ($attendance) {
            return (new LiveTrackingPresenter($attendance))->present();
        });

        $finalData = $presentedData->values()->all();

        return Json::item($finalData);
    }

    public function store(TrackLocationRequest $request, Attendance $attendance): JsonResponse
    {
        $trackingPoints = $request->getTrackingPoints();
        $this->trackingService->addTrackingPoints($attendance, $trackingPoints);

        return Json::success('Location data received successfully.');
    }
}
