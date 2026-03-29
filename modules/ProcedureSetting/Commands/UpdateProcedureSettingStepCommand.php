<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Commands;

class UpdateProcedureSettingStepCommand
{
    public function __construct(
        private int $id,
        private ?string $employee_id = null,
        private ?bool $is_accept = null,
        private ?bool $is_approve = null,
        private ?int $duration = null,
        private ?string $forms = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmployeeId(): ?string
    {
        return $this->employee_id;
    }

    public function getIsAccept(): ?bool
    {
        return $this->is_accept;
    }

    public function getIsApprove(): ?bool
    {
        return $this->is_approve;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function getForms(): ?string
    {
        return $this->forms;
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->employee_id !== null) {
            $data['employee_id'] = $this->employee_id;
        }

        if ($this->is_accept !== null) {
            $data['is_accept'] = $this->is_accept;
        }

        if ($this->is_approve !== null) {
            $data['is_approve'] = $this->is_approve;
        }

        if ($this->duration !== null) {
            $data['duration'] = $this->duration;
        }

        if ($this->forms !== null) {
            $data['forms'] = $this->forms;
        }

        return $data;
    }
}
