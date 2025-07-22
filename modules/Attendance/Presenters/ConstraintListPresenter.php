<?php

declare(strict_types=1);

namespace Modules\Attendance\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Attendance\Models\AttendanceConstraint;

class ConstraintListPresenter extends AbstractPresenter
{
    public function __construct(private AttendanceConstraint $constraint)
    {
    }

    public function present(bool $isListing = false): array
    {
        return [
            'id' => (string) $this->constraint->id,
            'constraint_name' => $this->constraint->constraint_name,
        ];
    }

}
