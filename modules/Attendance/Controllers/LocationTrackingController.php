<?php

declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Attendance\Requests\TrackLocationRequest;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Presenters\LiveTrackingPresenter;
use Modules\Attendance\Requests\LiveTrackingRequest;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Services\LocationTrackingService;

class LocationTrackingController
{
    // This constructor is correct.
    public function __construct(
        private LocationTrackingService $trackingService,
          private AttendanceService $attendanceService,
        private AttendanceConstraintService $constraintService
        )
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

    public function store(TrackLocationRequest $request): JsonResponse
    {
        $trackingPoints = $request->getTrackingPoints();

        $attendance = $this->attendanceService->getCurrentAttendance($request->user()->id);
        if (!$attendance) {
            return Json::error('No active attendance found for the user.');
        }

        $processedData = [];
        $hasMockLocation = false;

        // Prepare all tracking points for batch save
        $allTrackingPoints = [];

        // Process each tracking point
        foreach ($trackingPoints as $trackingData) {
            $trackingPoint = $trackingData['point'];
            $type = $trackingData['type'];
            $isMock = $trackingData['is_mock'];

            // Check for mock locations
            if ($isMock) {
                $hasMockLocation = true;
            }

            // Create enhanced tracking point with additional metadata
            $enhancedTrackingPoint = $trackingPoint->toArray();
            $enhancedTrackingPoint['type'] = $type;
            $enhancedTrackingPoint['is_mock'] = $isMock;
            // Map device uuid & accuracy if provided by client
            if (!empty($trackingData['uuid'])) {
                $enhancedTrackingPoint['uuid'] = $trackingData['uuid'];
                // keep device_id in sync for backward compatibility
                $enhancedTrackingPoint['device_id'] = $trackingData['uuid'];
            }
            if (isset($trackingData['accuracy'])) {
                $enhancedTrackingPoint['accuracy'] = (float) $trackingData['accuracy'];
            }

            // Add type-specific data
            if ($type === 'track') {
                $enhancedTrackingPoint['gps_status'] = $trackingData['gps_status'];
                $enhancedTrackingPoint['event'] = 'location';
            } elseif ($type === 'geofence') {
                $enhancedTrackingPoint['action'] = $trackingData['action'];
                $enhancedTrackingPoint['geofence_id'] = $trackingData['geofence_id'];
                // New naming as requested by mobile payload
                $enhancedTrackingPoint['geofence_action'] = $trackingData['action'];
                $enhancedTrackingPoint['event'] = 'geofence';
            }

            $enhancedTrackingPoint['processed_at'] = now()->toISOString();
            $allTrackingPoints[] = $enhancedTrackingPoint;

            $processedData[] = [
                'type' => $type,
                'is_mock' => $isMock,
                'processed_at' => now()->toISOString()
            ];
        }

        // Save all tracking points to location_tracking field
        if (!empty($allTrackingPoints)) {
            $this->trackingService->addTrackingPoints($attendance, $allTrackingPoints);
        }

        // Return error if any mock location detected
        if ($hasMockLocation) {
            return Json::error('Mock location detected. Please enable real GPS location.', 422);
        }

        $this->constraintService->validateAttendance($attendance, $request->all());

        return Json::success('Location data stored successfully.', [
            'payload' => method_exists($request, 'getOriginalPayload') ? $request->getOriginalPayload() : $request->all(),
            'processed_count' => count($processedData),
            'processed_data' => $processedData,
        ]);
    }
}
