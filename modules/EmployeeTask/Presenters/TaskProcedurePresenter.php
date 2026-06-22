<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Presenters;

use Modules\ProcedureSetting\Models\InternalProcedureTaken;

final class TaskProcedurePresenter
{
    public function __construct(
        private readonly InternalProcedureTaken $taken,
        private readonly int $stepNumber,
    ) {}

    public function toArray(): array
    {
        $t       = $this->taken;
        $setting = $t->procedureSetting;
        $user    = $t->takenByUser;

        return [
            'id'          => $t->id,
            'step_number' => $this->stepNumber,
            'name'        => $setting?->name,
            'icon'        => $setting?->icon,
            'percentage'  => $setting?->percentage,
            'form'        => $t->form,
            'taken_by'    => $user ? [
                'id'   => $user->id,
                'name' => $user->name,
            ] : null,
            'taken_at'    => $t->taken_at?->format('Y-m-d H:i:s'),
        ];
    }

    public static function collection(iterable $takenItems): array
    {
        $result = [];
        $step   = 1;
        foreach ($takenItems as $taken) {
            $result[] = (new self($taken, $step++))->toArray();
        }
        return $result;
    }
}
