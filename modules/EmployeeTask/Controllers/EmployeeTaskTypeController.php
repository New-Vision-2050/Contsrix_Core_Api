<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

use BasePackage\Shared\Presenters\Json;
use Modules\EmployeeTask\Services\EmployeeTaskTypeCRUDService;
use Modules\EmployeeTask\Presenters\EmployeeTaskTypePresenter;
use Modules\EmployeeTask\DTO\CreateEmployeeTaskTypeDTO;
use Illuminate\Http\Request;

class EmployeeTaskTypeController extends Controller
{
    public function __construct(private readonly EmployeeTaskTypeCRUDService $service) {}

    public function index(): JsonResponse
    {
        $types = $this->service->list(request()->all());
        return Json::items(EmployeeTaskTypePresenter::collection($types));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['key' => 'required|string|unique:employee_task_types,key', 'name' => 'required|string']);
        $type = $this->service->create(new CreateEmployeeTaskTypeDTO($request->key, $request->name));
        return Json::item(EmployeeTaskTypePresenter::single($type), message: 'Type created');
    }

    public function show(string $id): JsonResponse
    {
        return Json::item(EmployeeTaskTypePresenter::single($this->service->get($id)));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate(['name' => 'sometimes|string', 'key' => 'sometimes|string|unique:employee_task_types,key,'.$id]);
        $type = $this->service->update($id, $request->only(['name', 'key']));
        return Json::item(EmployeeTaskTypePresenter::single($type), message: 'Type updated');
    }

    public function destroy(string $id): JsonResponse
    {
        $type = $this->service->get($id);
        if ($type->tasks()->exists()) {
            return Json::error('Cannot delete type with associated tasks', 422);
        }
        $type->delete();
        return Json::success('Type deleted');
    }
}
