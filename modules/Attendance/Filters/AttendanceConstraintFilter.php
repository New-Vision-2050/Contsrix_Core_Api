<?php

declare(strict_types=1);

namespace Modules\Attendance\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class AttendanceConstraintFilter extends SearchModelFilter
{
    public $relations = ['users', 'company', 'branch'];

    public function name($name)
    {
        return $this->where('name', 'LIKE', "%{$name}%");
    }

    public function constraintType($type)
    {
        return $this->where('constraint_type', $type);
    }

    public function constraintName($name)
    {
        return $this->where('constraint_name', $name);
    }

    public function companyId($companyId)
    {
        return $this->where('company_id', $companyId);
    }

    public function userId($userId)
    {
        return $this->where('user_id', $userId);
    }

    public function departmentId($departmentId)
    {
        return $this->where('department_id', $departmentId);
    }

    public function branchId($branchId)
    {
        return $this->where('branch_id', $branchId);
    }

    public function branchName($branchName)
    {
        return $this->whereHas('branch', function ($query) use ($branchName) {
            $query->where('name', 'LIKE', "%{$branchName}%");
        });
    }

    public function isActive($isActive)
    {
        return $this->where('is_active', $isActive);
    }

    public function priority($priority)
    {
        return $this->where('priority', $priority);
    }

    public function priorityFrom($priority)
    {
        return $this->where('priority', '>=', $priority);
    }

    public function priorityTo($priority)
    {
        return $this->where('priority', '<=', $priority);
    }

    public function effectiveFrom($date)
    {
        return $this->whereDate('effective_from', '>=', $date);
    }

    public function effectiveTo($date)
    {
        return $this->whereDate('effective_to', '<=', $date);
    }

    public function isCurrentlyActive()
    {
        $now = now();
        return $this->where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $now);
            });
    }

    public function createdBy($userId)
    {
        return $this->where('created_by', $userId);
    }

    public function updatedBy($userId)
    {
        return $this->where('updated_by', $userId);
    }

    public function createdFrom($date)
    {
        return $this->whereDate('created_at', '>=', $date);
    }

    public function createdTo($date)
    {
        return $this->whereDate('created_at', '<=', $date);
    }

    public function updatedFrom($date)
    {
        return $this->whereDate('updated_at', '>=', $date);
    }

    public function updatedTo($date)
    {
        return $this->whereDate('updated_at', '<=', $date);
    }

    public function userName($name)
    {
        return $this->whereHas('users', function ($query) use ($name) {
            $query->where('name', 'LIKE', "%{$name}%");
        });
    }

    public function userEmail($email)
    {
        return $this->whereHas('users', function ($query) use ($email) {
            $query->where('email', 'LIKE', "%{$email}%");
        });
    }

    public function companyName($name)
    {
        return $this->whereHas('company', function ($query) use ($name) {
            $query->where('name', 'LIKE', "%{$name}%");
        });
    }

    public function hasConfig($configKey)
    {
        return $this->whereJsonContains('config', [$configKey => true]);
    }

    public function configValue($key, $value)
    {
        return $this->whereJsonContains("config->{$key}", $value);
    }

    public function employeeStatus($status)
    {
        return $this->whereHas('users', function ($query) use ($status) {
            $query->whereCompanyUserCompanyStatus($status, \Modules\CompanyUser\Enum\CompanyUserRole::EMPLOYEE->value);
        });
    }
}
