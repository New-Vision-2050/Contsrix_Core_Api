<?php

declare(strict_types=1);

namespace Modules\JobTitle\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\JobTitle\Commands\ChangeJobTitleStatusCommand;
use Ramsey\Uuid\Uuid;

class ChangeJobTitleStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:1,0',
        ];
    }

    public function createChangeJobTitleStatusCommand(): ChangeJobTitleStatusCommand
    {
        return new ChangeJobTitleStatusCommand(
            id: Uuid::fromString($this->route('id')),
            status: (int)$this->input('status')
        );
    }
}
