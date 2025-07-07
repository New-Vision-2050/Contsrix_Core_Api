<?php

declare(strict_types=1);

namespace Modules\Attendance\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class AttendanceConstraintViolationFilter extends SearchModelFilter
{
    public $relations = ['user', 'company', 'attendanceRecord', 'constraint'];

    public function companyId($companyId)
    {
        return $this->where('company_id', $companyId);
    }

    public function userId($userId)
    {
        return $this->where('user_id', $userId);
    }

    public function attendanceRecordId($recordId)
    {
        return $this->where('attendance_record_id', $recordId);
    }

    public function constraintId($constraintId)
    {
        return $this->where('constraint_id', $constraintId);
    }

    public function violationType($type)
    {
        return $this->where('violation_type', $type);
    }

    public function severity($severity)
    {
        return $this->where('severity', $severity);
    }

    public function status($status)
    {
        return $this->where('status', $status);
    }

    public function isResolved($isResolved)
    {
        if ($isResolved) {
            return $this->whereIn('status', ['resolved', 'dismissed']);
        }
        return $this->where('status', 'pending');
    }

    public function resolvedBy($userId)
    {
        return $this->where('resolved_by', $userId);
    }

    public function detectedFrom($date)
    {
        return $this->whereDate('detected_at', '>=', $date);
    }

    public function detectedTo($date)
    {
        return $this->whereDate('detected_at', '<=', $date);
    }

    public function resolvedFrom($date)
    {
        return $this->whereDate('resolved_at', '>=', $date);
    }

    public function resolvedTo($date)
    {
        return $this->whereDate('resolved_at', '<=', $date);
    }

    public function createdFrom($date)
    {
        return $this->whereDate('created_at', '>=', $date);
    }

    public function createdTo($date)
    {
        return $this->whereDate('created_at', '<=', $date);
    }

    public function userName($name)
    {
        return $this->whereHas('user', function ($query) use ($name) {
            $query->where('name', 'LIKE', "%{$name}%");
        });
    }

    public function userEmail($email)
    {
        return $this->whereHas('user', function ($query) use ($email) {
            $query->where('email', 'LIKE', "%{$email}%");
        });
    }

    public function companyName($name)
    {
        return $this->whereHas('company', function ($query) use ($name) {
            $query->where('name', 'LIKE', "%{$name}%");
        });
    }

    public function constraintType($type)
    {
        return $this->whereHas('constraint', function ($query) use ($type) {
            $query->where('constraint_type', $type);
        });
    }

    public function constraintName($name)
    {
        return $this->whereHas('constraint', function ($query) use ($name) {
            $query->where('constraint_name', $name);
        });
    }

    public function attendanceDate($date)
    {
        return $this->whereHas('attendanceRecord', function ($query) use ($date) {
            $query->whereDate('clock_in_time', $date);
        });
    }

    public function attendanceDateFrom($date)
    {
        return $this->whereHas('attendanceRecord', function ($query) use ($date) {
            $query->whereDate('clock_in_time', '>=', $date);
        });
    }

    public function attendanceDateTo($date)
    {
        return $this->whereHas('attendanceRecord', function ($query) use ($date) {
            $query->whereDate('clock_in_time', '<=', $date);
        });
    }

    public function hasResolutionNotes($hasNotes)
    {
        if ($hasNotes) {
            return $this->whereNotNull('resolution_notes');
        }
        return $this->whereNull('resolution_notes');
    }

    public function violationDetails($details)
    {
        return $this->where('violation_details', 'LIKE', "%{$details}%");
    }
}
