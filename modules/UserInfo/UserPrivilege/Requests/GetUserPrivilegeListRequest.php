<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Shared\Privilege\Services\PrivilegeCardConfigService;
use Modules\UserInfo\UserPrivilege\Filters\UserPrivilegeFilter;

class GetUserPrivilegeListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => 'integer',
            'page' => 'integer',
            'type' => ['nullable', 'string', Rule::in(array_keys(app(PrivilegeCardConfigService::class)->allConfigs()))],
            'type_privilege' => ['nullable', 'string', Rule::in(UserPrivilegeFilter::TYPE_PRIVILEGE_FILTER_VALUES)],
            'type_privilege_id' => 'nullable|uuid|exists:type_privileges,id',
        ];
    }
}
