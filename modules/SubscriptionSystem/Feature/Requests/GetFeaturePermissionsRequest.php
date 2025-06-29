<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Feature\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetFeaturePermissionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'feature_ids' => 'required|array',
            'feature_ids.*' => 'required|string|uuid|exists:features,id',
        ];
    }

    /**
     * Get the feature ids from the request
     *
     * @return array
     */
    public function getFeatureIds(): array
    {
        return $this->input('feature_ids', []);
    }
}
