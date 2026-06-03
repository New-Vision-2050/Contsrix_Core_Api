<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Shared\Privilege\Services\PrivilegeCardConfigService;

class GetUserPrivilegeListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => 'integer',
            'page' => 'integer',
            'type' => ['nullable', 'string', Rule::in(array_keys(app(PrivilegeCardConfigService::class)->allConfigs()))],
        ];
    }
}
