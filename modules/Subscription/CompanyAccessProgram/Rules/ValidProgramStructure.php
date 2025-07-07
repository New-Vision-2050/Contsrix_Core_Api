<?php declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\Program\Models\Program;
use Modules\SubEntity\Models\SubEntity;

class ValidProgramStructure implements Rule
{
    protected array $errors = [];

    public function passes($attribute, $value)
    {
        if (!is_array($value)) {
            $this->errors[] = "The $attribute must be an array.";
            return false;
        }

        $programIds = [];
        $subEntityIds = [];

        foreach ($value as $parentId => $data) {
            $programIds[] = $parentId;
            $subEntityIds = array_merge($subEntityIds, $data['sub_entities'] ?? []);

            foreach ($data['children'] ?? [] as $childId => $childData) {
                $programIds[] = $childId;
                $subEntityIds = array_merge($subEntityIds, $childData['sub_entities'] ?? []);
            }
        }

        $programs = Program::whereIn('id', $programIds)->get()->keyBy('id');
        $subEntities = SubEntity::whereIn('id', $subEntityIds)->get()->groupBy('program_id');

        foreach ($value as $parentId => $data) {
            if (!isset($programs[$parentId])) {
                $this->errors[] = "Parent program [$parentId] does not exist.";
            }

            foreach ($data['sub_entities'] ?? [] as $sid) {
                if (!$this->subEntityValid($sid, $parentId, $subEntities)) {
                    $this->errors[] = "Sub-entity [$sid] is not valid for program [$parentId].";
                }
            }

            foreach ($data['children'] ?? [] as $childId => $childData) {
                if (!isset($programs[$childId])) {
                    $this->errors[] = "Child program [$childId] does not exist.";
                    continue;
                }

                if ($programs[$childId]->parent_id !== $parentId) {
                    $this->errors[] = "Program [$childId] is not a child of [$parentId].";
                }

                foreach ($childData['sub_entities'] ?? [] as $sid) {
                    if (!$this->subEntityValid($sid, $childId, $subEntities)) {
                        $this->errors[] = "Sub-entity [$sid] is not valid for program [$childId].";
                    }
                }
            }
        }

        return empty($this->errors);
    }

    protected function subEntityValid(string $subEntityId, string $programId, $subEntities): bool
    {
        return $subEntities->has($programId) &&
               $subEntities[$programId]->pluck('id')->contains($subEntityId);
    }

    public function message(): string
    {
        return implode(' ', $this->errors) ?: 'The program structure is invalid.';
    }
}
