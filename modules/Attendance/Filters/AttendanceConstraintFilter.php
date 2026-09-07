<?php

declare(strict_types=1);

namespace Modules\Attendance\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class AttendanceConstraintFilter extends SearchModelFilter
{
    public $relations = ['users', 'company', 'branch'];

    public function search($search)
    {
        return $this->where('constraint_name', 'LIKE', "%{$search}%");
    }

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
        return $this->where(function ($query) use ($branchId) {
            $query->whereJsonContains('branch_ids', (string) $branchId)
                ->orWhereHas('managementHierarchies', function ($relation) use ($branchId) {
                    $relation->where('management_hierarchies.id', $branchId);
                });
        });
    }

    public function managementId($managementId)
    {
        return $this->where(function ($query) use ($managementId) {
            $query->whereExists(function ($professionalData) use ($managementId) {
                $professionalData->selectRaw('1')
                    ->from('user_professional_datas')
                    ->whereColumn(
                        'user_professional_datas.attendance_constraint_id',
                        'attendance_constraints.id'
                    )
                    ->where('user_professional_datas.management_id', $managementId);
            })->orWhereExists(function ($additionalAssignment) use ($managementId) {
                $additionalAssignment->selectRaw('1')
                    ->from('attendance_constraint_user')
                    ->join(
                        'user_professional_datas',
                        'user_professional_datas.user_id',
                        '=',
                        'attendance_constraint_user.user_id'
                    )
                    ->whereColumn(
                        'attendance_constraint_user.attendance_constraint_id',
                        'attendance_constraints.id'
                    )
                    ->where('user_professional_datas.management_id', $managementId);
            });
        });
    }

    public function jobTitleId($jobTitleId)
    {
        return $this->where(function ($query) use ($jobTitleId) {
            $query->whereExists(function ($professionalData) use ($jobTitleId) {
                $professionalData->selectRaw('1')
                    ->from('user_professional_datas')
                    ->whereColumn(
                        'user_professional_datas.attendance_constraint_id',
                        'attendance_constraints.id'
                    )
                    ->where('user_professional_datas.job_title_id', $jobTitleId);
            })->orWhereExists(function ($additionalAssignment) use ($jobTitleId) {
                $additionalAssignment->selectRaw('1')
                    ->from('attendance_constraint_user')
                    ->join(
                        'user_professional_datas',
                        'user_professional_datas.user_id',
                        '=',
                        'attendance_constraint_user.user_id'
                    )
                    ->whereColumn(
                        'attendance_constraint_user.attendance_constraint_id',
                        'attendance_constraints.id'
                    )
                    ->where('user_professional_datas.job_title_id', $jobTitleId);
            });
        });
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
}
