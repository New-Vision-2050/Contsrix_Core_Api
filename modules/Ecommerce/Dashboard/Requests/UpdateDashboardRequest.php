<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Dashboard\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\Dashboard\Commands\UpdateDashboardCommand;
use Modules\Ecommerce\Dashboard\Handlers\UpdateDashboardHandler;

class UpdateDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateDashboardCommand(): UpdateDashboardCommand
    {
        return new UpdateDashboardCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
