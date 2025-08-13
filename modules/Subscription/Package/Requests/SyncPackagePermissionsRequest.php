<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncPackagePermissionsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['nullable', 'string', 'exists:permissions,id'],
            'limits' => ['sometimes', 'array'],
            'limits.*.permission_id' => ['required_with:limits', 'string', 'exists:permissions,id'],
            'limits.*.number' => ['required_with:limits', 'integer', 'min:1'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the permission limits as an associative array.
     *
     * @return array [permission_id => limit_number]
     */
    public function getPermissionLimits(): array
    {
        $limits = $this->validated('limits', []);
        $limitMap = [];

        foreach ($limits as $limit) {
            $limitMap[$limit['permission_id']] = $limit['number'];
        }

        return $limitMap;
    }
}
