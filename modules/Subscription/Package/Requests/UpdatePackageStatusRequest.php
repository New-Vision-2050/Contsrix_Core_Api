<?php declare(strict_types=1);

namespace Modules\Subscription\Package\Requests;

use Ramsey\Uuid\Uuid;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Subscription\Package\Commands\UpdatePackageStatusCommand;
class UpdatePackageStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => 'required|boolean',
        ];
    }

    public function createUpdatePackageStatusCommand(): UpdatePackageStatusCommand
    {
        return new UpdatePackageStatusCommand(
            id: Uuid::fromString($this->route('id')),
            status: $this->get('status'),
        );
    }
}
