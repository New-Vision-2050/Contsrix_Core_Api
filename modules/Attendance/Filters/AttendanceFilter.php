<?php

declare(strict_types=1);

namespace Modules\Attendance\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;
use Carbon\Carbon;

class AttendanceFilter extends SearchModelFilter
{
    public $relations = ['user', 'company'];

    public function user($userId)
    {
        return $this->where('user_id', $userId);
    }

    public function companyId($companyId)
    {
        return $this->where('company_id', $companyId);
    }

    public function status($status)
    {
        return $this->where('status', $status);
    }

    public function startDate($date)
    {
        $start = Carbon::parse((string) $date, 'UTC')->startOfDay();

        return $this->where('start_time', '>=', $start);
    }

    public function endDate($date)
    {
        $endExclusive = Carbon::parse((string) $date, 'UTC')->addDay()->startOfDay();

        return $this->where('start_time', '<', $endExclusive);
    }

    public function clockInTimeFrom($time)
    {
        return $this->where('clock_in_time', '>=', $time);
    }

    public function clockInTimeTo($time)
    {
        return $this->where('clock_in_time', '<=', $time);
    }

    public function clockOutTimeFrom($time)
    {
        return $this->where('clock_out_time', '>=', $time);
    }

    public function clockOutTimeTo($time)
    {
        return $this->where('clock_out_time', '<=', $time);
    }

    public function ipAddress($ip)
    {
        return $this->where('ip_address', 'LIKE', "%{$ip}%");
    }

    public function approvedBy($approvedBy)
    {
        return $this->where('approved_by', $approvedBy);
    }

    public function isApproved($isApproved)
    {
        if ($isApproved) {
            return $this->whereNotNull('approved_at');
        }
        return $this->whereNull('approved_at');
    }

    public function workHoursFrom($hours)
    {
        return $this->where('work_hours', '>=', $hours);
    }

    public function workHoursTo($hours)
    {
        return $this->where('work_hours', '<=', $hours);
    }

    public function overtimeHoursFrom($hours)
    {
        return $this->where('overtime_hours', '>=', $hours);
    }

    public function overtimeHoursTo($hours)
    {
        return $this->where('overtime_hours', '<=', $hours);
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

    public function hasBreaks($hasBreaks)
    {
        if ($hasBreaks) {
            return $this->where('break_duration', '>', 0);
        }
        return $this->where('break_duration', 0);
    }

    public function breakDurationFrom($minutes)
    {
        return $this->where('break_duration', '>=', $minutes);
    }

    public function breakDurationTo($minutes)
    {
        return $this->where('break_duration', '<=', $minutes);
    }

    public function isLate($isLate)
    {
        if ($isLate) {
            return $this->where('is_late', true);
        }
        return $this->where('is_late', false);
    }

    public function isEarlyLeave($isEarlyLeave)
    {
        if ($isEarlyLeave) {
            return $this->where('is_early_leave', true);
        }
        return $this->where('is_early_leave', false);
    }

    public function userSearch($term)
    {
        return $this->whereHas('user', function ($query) use ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', "%{$term}%")
                ->orWhere('email', 'LIKE', "%{$term}%");
            });
        });
    }
    public function searchText($search)
    {
        return $this->whereHas('user', function ($query) use ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%");
            });
        });
    }
    public function management($managementId)
    {

        return $this->whereHas('user.userProfessionalData', function ($query) use ($managementId) {
            $query->where('management_id', $managementId);
        });
    }

    public function branch($branchId)
    {
        return $this->whereHas('user.userProfessionalData', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        });
    }

    public function constraint($constraintId)
    {
       return $this->whereHas('user.professionalData', function ($query) use ($constraintId) {
            $query->where('attendance_constraint_id',$constraintId);
        });
    }
}
