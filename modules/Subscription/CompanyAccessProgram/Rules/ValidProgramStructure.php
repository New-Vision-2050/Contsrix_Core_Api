<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Arr;
use Modules\Program\Models\Program;
use Modules\SubEntity\Models\SubEntity;

class ValidProgramStructure implements Rule
{
    protected array $errors = [];
    private array $programIds = [];
    private array $subEntityIds = [];
    private ?array $dbPrograms = null;
    private ?object $dbSubEntities = null;

    public function passes($attribute, $value): bool
    {
        if (!is_array($value)) {
            $this->errors[] = "The $attribute must be an array.";
            return false;
        }

        // Step 1: Recursively collect all IDs from the input
        $this->collectIds($value);

        // Step 2: Fetch all required data from the database in two queries
        $this->fetchDataFromDb();

        // Step 3: Recursively validate the structure against the DB data
        $this->validatePrograms($value);

        return empty($this->errors);
    }

    private function collectIds(array $programs): void
    {
        foreach ($programs as $program) {
            if (empty($program['id'])) {
                continue;
            }
            $this->programIds[] = $program['id'];

            if (!empty($program['sub_entities']) && is_array($program['sub_entities'])) {
                $this->subEntityIds = array_merge($this->subEntityIds, $program['sub_entities']);
            }

            if (!empty($program['children']) && is_array($program['children'])) {
                $this->collectIds($program['children']);
            }
        }
    }

    private function fetchDataFromDb(): void
    {
        $this->dbPrograms = Program::whereIn('id', array_unique($this->programIds))->get()->keyBy('id')->all();
        $this->dbSubEntities = SubEntity::whereIn('id', array_unique($this->subEntityIds))->get()->groupBy('program_id');
    }

    private function validatePrograms(array $programs, ?string $parentId = null): void
    {
        foreach ($programs as $index => $program) {
            $programId = $program['id'] ?? null;

            if (!$programId || !isset($this->dbPrograms[$programId])) {
                $this->errors[] = "Program with ID [$programId] at index [$index] does not exist.";
                continue;
            }

            $dbProgram = $this->dbPrograms[$programId];

            if ($parentId && $dbProgram->parent_id !== $parentId) {
                $this->errors[] = "Program [$programId] is not a child of [$parentId].";
            }

            foreach ($program['sub_entities'] ?? [] as $subEntityId) {
                if (!$this->isSubEntityValid($subEntityId, $programId)) {
                    $this->errors[] = "Sub-entity [$subEntityId] is not valid for program [$programId].";
                }
            }

            if (!empty($program['children']) && is_array($program['children'])) {
                $this->validatePrograms($program['children'], $programId);
            }
        }
    }

    private function isSubEntityValid(string $subEntityId, string $programId): bool
    {
        return $this->dbSubEntities->has($programId) &&
               $this->dbSubEntities[$programId]->pluck('id')->contains($subEntityId);
    }

    public function message(): string
    {
        return implode(' ', $this->errors) ?: 'The program structure is invalid.';
    }
}
