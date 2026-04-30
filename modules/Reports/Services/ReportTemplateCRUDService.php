<?php

declare(strict_types=1);

namespace Modules\Reports\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Reports\DTO\CreateReportDTO;
use Modules\Reports\DTO\CreateReportTemplateDTO;
use Modules\Reports\DTO\ReportWizardConfigDTO;
use Modules\Reports\DTO\ReportWizardStep1DTO;
use Modules\Reports\DTO\UpdateReportTemplateDTO;
use Modules\Reports\Models\Report;
use Modules\Reports\Models\ReportTemplate;
use Modules\Reports\Repositories\ReportTemplateRepository;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ReportTemplateCRUDService
{
    public function __construct(
        private ReportTemplateRepository $repository,
        private ReportCRUDService        $reportCRUDService,
    ) {
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page:    $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): ReportTemplate
    {
        return $this->repository->getTemplate($id);
    }

    public function create(CreateReportTemplateDTO $dto): ReportTemplate
    {
        return $this->repository->create([
            'id'          => Uuid::uuid4()->toString(),
            'company_id'  => tenant('id'),
            'created_by'  => Auth::id(),
            'name'        => $dto->name,
            'description' => $dto->description,
            'config'      => $dto->config->toArray(),
            'is_active'   => true,
        ]);
    }

    public function update(UpdateReportTemplateDTO $dto): ReportTemplate
    {
        $data = [
            'name'        => $dto->name,
            'description' => $dto->description,
            'config'      => $dto->config->toArray(),
        ];
        if ($dto->isActive !== null) {
            $data['is_active'] = $dto->isActive;
        }

        return $this->repository->updateTemplate($dto->id, $data);
    }

    public function delete(UuidInterface $id): bool
    {
        return $this->repository->deleteById($id);
    }

    /**
     * Materialise a saved template into a generated Report, honouring any
     * per-run overrides (different year/month/week/quarter, custom name).
     *
     * @param array<string,mixed> $overrides
     */
    public function generateReport(UuidInterface $id, array $overrides = []): Report
    {
        $template = $this->repository->getTemplate($id);
        $config   = ReportWizardConfigDTO::fromArray($template->config);

        if ($overrides !== []) {
            $step1Array = $config->step1->toArray();
            foreach (['year', 'month', 'week', 'quarter'] as $key) {
                if (array_key_exists($key, $overrides) && $overrides[$key] !== null) {
                    $step1Array[$key] = $overrides[$key];
                }
            }
            $config = new ReportWizardConfigDTO(
                step1: ReportWizardStep1DTO::fromArray($step1Array),
                step2: $config->step2,
                step3: $config->step3,
                step4: $config->step4,
                step5: $config->step5,
            );
        }

        $name = null;
        if (!empty($overrides['name']) && is_array($overrides['name'])) {
            $name = $overrides['name'];
        }

        return $this->reportCRUDService->create(new CreateReportDTO(
            config:     $config,
            name:       $name,
            templateId: $template->id,
        ));
    }
}
