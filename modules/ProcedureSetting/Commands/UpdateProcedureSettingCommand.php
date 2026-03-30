<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateProcedureSettingCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private string $type,
        private string $execute_type,
        private ?string $icon = null,
        private ?float $percentage = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getExecuteType(): string
    {
        return $this->execute_type;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getPercentage(): ?float
    {
        return $this->percentage;
    }

    public function toArray(): array
    {
        $data = [
            'name'         => $this->name,
            'type'         => $this->type,
            'execute_type' => $this->execute_type,
        ];

        if ($this->icon !== null) {
            $data['icon'] = $this->icon;
        }

        if ($this->percentage !== null) {
            $data['percentage'] = $this->percentage;
        }

        return $data;
    }
}
