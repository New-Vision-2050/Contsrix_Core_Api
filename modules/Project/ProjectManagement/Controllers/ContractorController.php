<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Project\ProjectManagement\Models\Contractor;

class ContractorController extends Controller
{
    /**
     * GET /api/v1/projects/notifications/contractors
     *
     * List active contractors for the current tenant. Used to populate the
     * contractor dropdown on the project notification form.
     */
    public function index(Request $request): JsonResponse
    {
        $contractors = Contractor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'number', 'mobile', 'notes']);

        return Json::items($contractors->map(fn ($contractor) => [
            'id'     => $contractor->id,
            'name'   => $contractor->name,
            'number' => $contractor->number,
            'mobile' => $contractor->mobile,
            'notes'  => $contractor->notes,
        ])->all());
    }
}
