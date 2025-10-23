<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBusinessActivity\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Ecommerce\EcoBusinessActivity\Commands\Dashboard\UpdateEcoBusinessActivityDashboardCommand;
class UpdateEcoBusinessActivityDashboardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateEcoBusinessActivityCommand(): UpdateEcoBusinessActivityDashboardCommand
    {
        return new UpdateEcoBusinessActivityDashboardCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
