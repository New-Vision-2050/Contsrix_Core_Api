<?php

declare(strict_types=1);

namespace Modules\Attendance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Attendance\DataClasses\LocationTrackingPoint;

class TrackLocationRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     * Normalize single object to array format.
     */
    protected function prepareForValidation()
    {
        $input = $this->all();
        
        // If input is a single object (has 'type' key directly), wrap it in an array
        if (isset($input['type']) && is_string($input['type'])) {
            $this->replace([$input]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     * Supports both array of tracking data and single object.
     */
    public function rules(): array
    {
        return [
            '*' => ['required', 'array'],
            '*.type' => ['required', 'string', 'in:track,geofence'],
            '*.lat' => ['required', 'numeric', 'between:-90,90'],
            '*.lng' => ['required', 'numeric', 'between:-180,180'],
            '*.timestamp' => ['required', 'string'],
            '*.is_mock' => ['required', 'boolean'],
            '*.uuid' => ['sometimes', 'string', 'max:255'],
            '*.accuracy' => ['sometimes', 'numeric', 'min:0'],

            '*.gps_status' => ['required_if:*.type,track', 'string'],

            '*.action' => ['required_if:*.type,geofence', 'string'],
            '*.id' => ['required_if:*.type,geofence', 'string'],
        ];
    }

    /**
     * Customize validation failure response to include expected payload shape and examples.
     */
    protected function failedValidation(Validator $validator)
    {
        $expectedFormatSingle = [
            'type' => 'track|geofence',
            'lat' => 0.0,
            'lng' => 0.0,
            'is_mock' => false,
            'timestamp' => '2025-12-23T19:08:00Z',
            'uuid' => 'device-identifier (optional)',
            'accuracy' => 5.0,
            'gps_status' => 'required when type=track',
            'action' => 'enter|exit (required when type=geofence)',
            'id' => 'geofence_identifier (required when type=geofence)',
        ];

        $exampleSingle = [
            'type' => 'track',
            'lat' => 29.996442,
            'lng' => 30.9024529,
            'is_mock' => false,
            'gps_status' => 'enabled',
            'timestamp' => '2025-12-23T19:08:00Z',
            'uuid' => 'iPhone-14-Pro',
            'accuracy' => 7.5,
        ];

        $exampleArray = [
            [
                'type' => 'track',
                'lat' => 29.996442,
                'lng' => 30.9024529,
                'is_mock' => false,
                'gps_status' => 'enabled',
                'timestamp' => '2025-12-23T19:08:00Z',
                'uuid' => 'iPhone-14-Pro',
                'accuracy' => 7.5,
            ],
            [
                'type' => 'geofence',
                'lat' => 29.996442,
                'lng' => 30.9024529,
                'action' => 'ENTER',
                'id' => 'office_main_branch',
                'is_mock' => false,
                'timestamp' => '2025-12-23T19:08:00Z',
                'uuid' => 'iPhone-14-Pro',
                'accuracy' => 6.2,
            ],
        ];

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'فشل التحقق من الصحة',
            'errors' => $validator->errors(),
            'note' => 'يمكنك إرسال كائن واحد أو مصفوفة من الكائنات / You can send a single object or an array of objects',
            'expected_format' => $expectedFormatSingle,
            'example_single_object' => $exampleSingle,
            'example_array' => $exampleArray,
            'received' => $this->all(),
        ], 422));
    }

    /**
     * Get all tracking data from the array
     *
     * @return array
     */
    public function getTrackingData(): array
    {
        return $this->validated();
    }

    /**
     * Convert tracking data to LocationTrackingPoint objects
     *
     * @return array
     */
    public function getTrackingPoints(): array
    {
        $trackingData = $this->getTrackingData();
        $points = [];

        foreach ($trackingData as $data) {
            $pointData = [
                'latitude' => $data['lat'],
                'longitude' => $data['lng'],
                'timestamp' => $data['timestamp'],
                'accuracy' => $data['accuracy'] ?? 5.0,
                'device_id' => $data['uuid'] ?? 'mobile-app',
                'app_version' => '1.0.0',
                'battery_level' => 100,
                'network_type' => '4G',
                'location_source' => $data['type'] === 'track' ? 'GPS' : 'Network',
            ];

            $points[] = [
                'point' => LocationTrackingPoint::fromArray($pointData),
                'type' => $data['type'],
                'is_mock' => $data['is_mock'],
                'gps_status' => $data['gps_status'] ?? null,
                'action' => $data['action'] ?? null,
                'geofence_id' => $data['id'] ?? null,
                'uuid' => $data['uuid'] ?? null,
                'accuracy' => $data['accuracy'] ?? 5.0,
            ];
        }

        return $points;
    }
}
