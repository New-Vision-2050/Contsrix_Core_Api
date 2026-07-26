<?php

namespace Modules\Project\ProjectType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class EvaluateViolationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'violations' => ['required', 'array', 'min:1'],
            'violations.*.violation_id' => ['required', 'uuid', 'exists:violations,id'],
            'violations.*.weight' => ['nullable', 'numeric'],
            'violations.*.status' => ['required', Rule::in(['violation_found', 'no_violation', 'not_applicable'])],

            'violations.*.images' => ['nullable', 'array', 'max:3'],
            'violations.*.images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
      ];
    }

    /**
     * Violations payload with uploaded files merged under each item's `images` key.
     *
     * Multipart fields arrive as violations[n][images][] and are not included in input().
     *
     * @return array<int, array<string, mixed>>
     */
    public function violationsWithImages(): array
    {
        $violations = $this->input('violations', []);
        $files = $this->file('violations', []) ?: [];

        foreach ($violations as $index => $violation) {
            $images = Arr::wrap($files[$index]['images'] ?? []);
            $violations[$index]['images'] = array_values(array_filter(
                $images,
                fn ($file) => $file instanceof UploadedFile && $file->isValid()
            ));
        }

        return $violations;
    }
}
