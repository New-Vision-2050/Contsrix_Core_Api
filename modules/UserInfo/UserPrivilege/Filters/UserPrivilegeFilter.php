<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;
use Modules\Shared\Privilege\Services\PrivilegeCardConfigService;

class UserPrivilegeFilter extends SearchModelFilter
{
    public $relations = [];

    public const TYPE_PRIVILEGE_INDIVIDUAL = 'Individual';
    public const TYPE_PRIVILEGE_FAMILY = 'Family';

    public const TYPE_PRIVILEGE_FILTER_VALUES = [
        self::TYPE_PRIVILEGE_INDIVIDUAL,
        self::TYPE_PRIVILEGE_FAMILY,
        'individual',
        'family',
    ];

    public function name($name)
    {
        return $this->where('name', $name);
    }

    public function type(string $type)
    {
        return $this->where(function ($query) use ($type) {
            $query->whereHas('privilege', function ($privilegeQuery) use ($type) {
                $privilegeQuery->where('type', $type);
            });

            if ($type === PrivilegeCardConfigService::TYPE_HEALTH_INSURANCE) {
                $query->orWhereNotNull('medical_insurance_id');
            }
        });
    }

    public function typePrivilege(string $value)
    {
        if ($this->isUuid($value)) {
            return $this->where('type_privilege_id', $value);
        }

        return $this->whereHas('typePrivilege', function ($query) use ($value) {
            $query->whereHas('translations', function ($translationQuery) use ($value) {
                $translationQuery->where('field', 'name')
                    ->where(function ($contentQuery) use ($value) {
                        $contentQuery->where('content', $value)
                            ->orWhereRaw('LOWER(content) = ?', [strtolower($value)]);
                    });
            });
        });
    }

    public function typePrivilegeId(string $value)
    {
        return $this->where('type_privilege_id', $value);
    }

    private function isUuid(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value
        );
    }
}
