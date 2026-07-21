<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Project\ProjectManagement\Enums\ProjectRequirementEvaluationStatus;
use Modules\Project\ProjectManagement\Enums\ProjectRequirementRepetition;
use Modules\Project\ProjectManagement\Models\ProjectRequirement;

/** @extends Factory<ProjectRequirement> */
class ProjectRequirementFactory extends Factory
{
    protected $model = ProjectRequirement::class;

    public function definition(): array
    {
        $repetition = $this->faker->randomElement(ProjectRequirementRepetition::values());

        return [
            'id' => (string) Str::uuid(),
            'requirement_code' => 'REQ-'.$this->faker->unique()->numerify('####'),
            'required_document_name' => $this->faker->words(3, true),
            'document' => $this->faker->words(4, true),
            'document_type' => 'Technical Submittal',
            'specialization' => $this->faker->randomElement(['Civil', 'Electrical', 'Mechanical']),
            'stage' => $this->faker->randomElement(['Owner', 'Contractor', 'Consultant']),
            'sending_entity' => 'Consultant',
            'review_entity' => 'Contractor',
            'repetition' => $repetition,
            'repetition_interval_type' => ProjectRequirementRepetition::intervalTypeFor($repetition),
            'evaluation_status' => ProjectRequirementEvaluationStatus::default(),
            'completion_percentage' => 0,
        ];
    }
}
