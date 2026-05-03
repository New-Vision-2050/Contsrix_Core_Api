<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Modules\Attendance\Models\AppliedAttendanceConstraint;
use Modules\Attendance\Models\Attendance;
use Modules\User\Models\User;
class AutoAttendanceService
{
    public function createAttendanceRecord(array $data, Carbon $startDateTime=null): Attendance
    {
        // Keep status constrained to lifecycle states stored in attendances.status.
        $status = $data['status'] ?? Attendance::STATUS_COMPLETED;

        $attendanceData = [
            'user_id' => $data['user_id'],
            'company_id' => $data['company_id'],
            'clock_in_time' => $data['clock_in_time'] ?? null,
            'clock_in_location' => $data['clock_in_location'] ?? null,
            'start_time' => $startDateTime,
            'notes' => $data['notes'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'status' => $status,
            'day_status' => $data['day_status'],
            'timezone' => $data['timezone'] ?? config('app.timezone'),
            'is_absent' => $data['is_absent'] ?? 0,
            'is_late' => $data['is_late'] ?? 0,
            'is_holiday' => $data['is_holiday'] ?? 0,
        ];

        $attendance = Attendance::create($attendanceData);

        $constraint = $attendance->user->professionalData->attendanceConstraint;

        if ($constraint) {
            // Create a record in the pivot table with the required fields
            AppliedAttendanceConstraint::create([
                'attendance_id' => $attendance->id,
                'constraint_snapshot' => $constraint->toArray(),
                'company_id' => $attendance->company_id,
            ]);
        }

        return $attendance;
    }


    public function generateAttendanceUsers($companyId,$userId=null,$startDatePram=null,$endDatePram=null)
    {
        $timezone = getTimeZoneBranchByRequest() ?? config('app.timezone');

        $startDate = $startDatePram ?? Carbon::now($timezone)->startOfMonth()->startOfDay();
        $endDate =  $endDatePram ?? Carbon::now($timezone)->endOfMonth()->endOfDay();

        $period = CarbonPeriod::create($startDate, $endDate);
        $allDates = collect($period->toArray());


        $allRelevantUsers = User::where('company_id', $companyId)
            ->withoutTenancy()
            ->whereNotIn('email', config('constrix.emails'))
            ->when($userId, function ($query) use ($userId) {
                return $query->where('id', $userId);
            })->has('professionalData.attendanceConstraint')
            ->with(['professionalData.attendanceConstraint'])
            ->get();

            $allRelevantUserIds = $allRelevantUsers->pluck('id')->toArray();



        $realAttendanceRecords = Attendance::query()
            ->select([
                'id', 'user_id', 'company_id', 'status', 'is_late', 'is_absent',
                'is_holiday', 'day_status', 'clock_in_time', 'clock_out_time',
                'start_time', 'overtime_hours'
            ])
            ->whereIn('user_id', $allRelevantUserIds)
            ->whereBetween('start_time', [$startDate, $endDate])
            ->orderBy('start_time')
            ->get();

        $groupedAttendance = [];
        foreach ($realAttendanceRecords as $record) {
            $userId = (string)$record->user_id;
            $dateKey = Carbon::parse($record->start_time)->timezone($timezone)->format('Y-m-d');

            if (!isset($groupedAttendance[$userId])) {
                $groupedAttendance[$userId] = [];
            }
            if (!isset($groupedAttendance[$userId][$dateKey])) {
                $groupedAttendance[$userId][$dateKey] = collect();
            }
            $groupedAttendance[$userId][$dateKey]->push($record);
        }


        foreach ($allRelevantUsers as $user) {
            $constraint = $user->professionalData?->attendanceConstraint;
            if($constraint){


            $constraintHolidays = collect($constraint->constraint_config['time_rules']['holidays'] ?? [])
                                    ->pluck('date')
                                    ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
                                    ->toArray();

            foreach ($allDates as $date) {
                $dateString = $date->format('Y-m-d');

                if (in_array($dateString, $constraintHolidays)) {
                    $status = 'Official Holiday';
                } else {
                    $dayOfWeek = strtolower($date->englishDayOfWeek);
                    $schedule = $constraint->constraint_config['time_rules']['weekly_schedule'][$dayOfWeek] ?? null;

                if ($schedule && isset($schedule['enabled']) && $schedule['enabled']) {
                    $userId = (string)$user->id;

                    $dayPeriods = $schedule['periods'] ?? [];

                    foreach ($dayPeriods as $index => $periodTime) {
                        $periodName = 'فترة ' . ($index + 1);
                        $periodStart = Carbon::parse($dateString . ' ' . $periodTime['start_time']);


                            $existingAbsentRecord = Attendance::where('user_id', $user->id)
                                ->whereDate('start_time', $dateString)
                                ->whereTime('start_time', $periodTime['start_time'])
                                ->first();

                            if (!$existingAbsentRecord) {
                                $this->createAttendanceRecord(
                                    [
                                        'user_id' => $user->id,
                                        'company_id' => $user->company_id,
                                        'day_status' => 'work_day',
                                        'timezone' => $timezone,
                                        'notes' => 'Auto-generated absent record for ' . $periodName . ' on a workday (missed period).',
                                        'status' => Attendance::STATUS_ABSENT,
                                        'is_absent' => 1,
                                    ],
                                    $periodStart,
                                );
                            }
                        }
                    }else {
                        $existingHolidayRecord = Attendance::where('user_id', $user->id)
                            ->whereDate('start_time', $dateString)
                            ->first();

                        if (!$existingHolidayRecord) {
                            $carbonDate = Carbon::parse($dateString, $timezone)->startOfDay();
                            $this->createAttendanceRecord([
                                    'user_id' => $user->id,
                                    'company_id' => $user->company_id,
                                    'day_status' => 'holiday',
                                    'timezone' => $timezone,
                                    'notes' => 'Auto-generated holiday record.',
                                    'status' => Attendance::STATUS_HOLIDAY,
                                    'is_holiday' => 1,
                                ],$carbonDate);
                        }
                    }

                }

            }

        }
        }
    }
}

