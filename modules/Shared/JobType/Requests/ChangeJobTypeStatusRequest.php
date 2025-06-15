<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Shared\JobType\Commands\ChangeJobTypeStatusCommand;
use Ramsey\Uuid\Uuid;

class ChangeJobTypeStatusRequest extends FormRequest
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

    public function createChangeJobTypeStatusCommand(): ChangeJobTypeStatusCommand
    {
        return new ChangeJobTypeStatusCommand(
            id: Uuid::fromString($this->route('id')),
            status: (int)$this->input('status')
        );
    }
}
