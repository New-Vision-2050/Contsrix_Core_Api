<?php

namespace Modules\Subscription\Package\Requests;


use Illuminate\Foundation\Http\FormRequest;
use Modules\Subscription\Package\DTO\PackageFeatureDTO;
use Modules\Subscription\Package\DTO\AttachPackageFeaturesDTO;

class AttachPackageFeaturesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'package_id' => ['required', 'uuid', 'exists:packages,id'],
            'features' => ['required', 'array'],
            'features.*.permission_id' => ['required', 'uuid', 'distinct', 'exists:permissions,id'],
            'features.*.limit' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function createAttachPackageFeaturesDTO(): AttachPackageFeaturesDTO
    {
        $features = collect($this->input('features', []))
            ->map(fn(array $item) => new PackageFeatureDTO(
                permissionId: $item['permission_id'],
                limit: array_key_exists('limit', $item) ? (int) $item['limit'] : null
            ))
            ->all();

        return new AttachPackageFeaturesDTO(
            packageId: $this->input('package_id'),
            features: $features,
        );
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
