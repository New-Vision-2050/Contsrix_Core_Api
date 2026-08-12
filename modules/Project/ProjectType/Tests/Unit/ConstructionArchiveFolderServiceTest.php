<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\ArchiveLibrary\File\Models\File as ArchiveFile;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Services\ProjectNotificationService;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectType\Services\ConstructionArchiveFolderService;
use Tests\TestCase;

final class ConstructionArchiveFolderServiceTest extends TestCase
{
    use DatabaseTransactions;

    private Company $company;

    private ProjectManagement $project;

    private Folder $projectRoot;

    private ConstructionArchiveFolderService $service;

    private string $namePrefix;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->databaseReady()) {
            $this->markTestSkipped('Database seed prerequisites missing for construction archive folder tests.');
        }

        $this->project = ProjectManagement::withoutGlobalScopes()
            ->whereNotNull('company_id')
            ->first();

        if (! $this->project) {
            $this->markTestSkipped('Need at least one project with company_id in DB.');
        }

        $this->company = Company::withoutGlobalScopes()->find($this->project->company_id);
        if (! $this->company) {
            $this->markTestSkipped('Project company not found.');
        }

        tenancy()->initialize($this->company);

        $projectRoot = Folder::query()
            ->withoutTenancy()
            ->where('id', $this->project->id)
            ->whereNull('parent_id')
            ->first()
            ?? Folder::query()
                ->withoutTenancy()
                ->where('project_id', $this->project->id)
                ->where('company_id', $this->company->id)
                ->whereNull('parent_id')
                ->orderBy('created_at')
                ->first();

        if (! $projectRoot) {
            $projectRoot = Folder::query()->withoutTenancy()->create([
                'id' => (string) Str::uuid(),
                'name' => $this->project->name,
                'parent_id' => null,
                'project_id' => $this->project->id,
                'company_id' => $this->company->id,
                'access_type' => 'public',
                'status' => 1,
                'type' => Folder::TYPE_SYSTEM,
            ]);
        }

        $this->projectRoot = $projectRoot;

        $this->namePrefix = 'CON-TEST-'.Str::upper(Str::random(8));
        $this->service = app(ConstructionArchiveFolderService::class);
    }

    public function test_creates_construction_and_work_order_folders_under_project_root(): void
    {
        $woName = $this->namePrefix.'-WO-1001';

        $permit = ProjectOrderPermit::query()->create([
            'project_id' => $this->project->id,
            'name' => $woName,
        ]);

        $folder = $this->service->ensureWorkOrderFolder($permit);

        $this->assertNotNull($folder);
        $this->assertSame($woName, $folder->name);
        $this->assertSame(Folder::TYPE_SYSTEM, $folder->type);
        $this->assertSame(0, ArchiveFile::query()->withoutTenancy()->where('folder_id', $folder->id)->count());

        $construction = Folder::query()
            ->withoutTenancy()
            ->where('name', ConstructionArchiveFolderService::CONSTRUCTION_FOLDER_NAME)
            ->where('parent_id', $this->projectRoot->id)
            ->where('project_id', $this->project->id)
            ->first();

        $this->assertNotNull($construction);
        $this->assertSame($construction->id, $folder->parent_id);
        $this->assertSame($this->projectRoot->id, $construction->parent_id);
    }

    public function test_construction_and_emergency_share_the_same_parent(): void
    {
        Folder::query()->withoutTenancy()->firstOrCreate(
            [
                'name' => ProjectNotificationService::MAINTENANCE_EMERGENCY_FOLDER_NAME,
                'parent_id' => $this->projectRoot->id,
                'project_id' => $this->project->id,
                'company_id' => $this->company->id,
            ],
            [
                'access_type' => 'public',
                'status' => 1,
                'type' => Folder::TYPE_SYSTEM,
            ],
        );

        $permit = ProjectOrderPermit::query()->create([
            'project_id' => $this->project->id,
            'name' => $this->namePrefix.'-SHARED-PARENT',
        ]);

        $this->service->ensureWorkOrderFolder($permit);

        $this->assertTrue(
            $this->service->shareSameParentAsEmergency(
                (string) $this->project->id,
                (string) $this->company->id,
            )
        );
    }

    public function test_is_idempotent_and_skips_when_no_named_work_orders_for_prefix(): void
    {
        $woName = $this->namePrefix.'-IDEM';

        ProjectOrderPermit::query()->create([
            'project_id' => $this->project->id,
            'name' => $woName,
        ]);

        $second = $this->service->ensureProjectWorkOrderFolders(
            (string) $this->project->id,
            (string) $this->company->id,
        );
        $third = $this->service->ensureProjectWorkOrderFolders(
            (string) $this->project->id,
            (string) $this->company->id,
        );

        $this->assertGreaterThanOrEqual(1, $second['work_order_folders']);
        $this->assertSame($second['work_order_folders'], $third['work_order_folders']);

        $count = Folder::query()
            ->withoutTenancy()
            ->where('name', $woName)
            ->where('parent_id', $second['construction_folder']?->id)
            ->where('project_id', $this->project->id)
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_rename_updates_existing_work_order_folder_name(): void
    {
        $oldName = $this->namePrefix.'-OLD';
        $newName = $this->namePrefix.'-NEW';

        $permit = ProjectOrderPermit::query()->create([
            'project_id' => $this->project->id,
            'name' => $oldName,
        ]);

        $this->service->ensureWorkOrderFolder($permit);

        $permit->name = $newName;
        $permit->save();

        $this->service->syncWorkOrderFolderName($permit, $oldName, $newName);

        $construction = $this->service->findConstructionCategoryFolder(
            (string) $this->project->id,
            (string) $this->company->id,
        );

        $this->assertSame(
            0,
            Folder::query()
                ->withoutTenancy()
                ->where('name', $oldName)
                ->where('parent_id', $construction?->id)
                ->where('project_id', $this->project->id)
                ->count()
        );
        $this->assertSame(
            1,
            Folder::query()
                ->withoutTenancy()
                ->where('name', $newName)
                ->where('parent_id', $construction?->id)
                ->where('project_id', $this->project->id)
                ->count()
        );
    }

    private function databaseReady(): bool
    {
        try {
            return Schema::hasTable('companies')
                && Schema::hasTable('projects')
                && Schema::hasTable('folders')
                && Schema::hasTable('project_order_permit');
        } catch (\Throwable) {
            return false;
        }
    }
}
