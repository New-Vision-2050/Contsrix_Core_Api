<?php

declare(strict_types=1);

namespace Modules\Subscription\DTO;

class FeaturePermissionDTO
{
    /**
     * @param string $id
     * @param string $name
     * @param string $guardName
     * @param bool $status
     */
    public function __construct(
        private string $id,
        private string $name,
        private string $guardName,
        private bool $status
    ) {
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guardName,
            'status' => $this->status,
        ];
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getGuardName(): string
    {
        return $this->guardName;
    }

    /**
     * @return bool
     */
    public function getStatus(): bool
    {
        return $this->status;
    }
}
