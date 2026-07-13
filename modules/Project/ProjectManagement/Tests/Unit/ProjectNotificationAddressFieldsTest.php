<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Unit;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Modules\Project\ProjectManagement\DTO\CreateProjectNotificationDTO;
use Modules\Project\ProjectManagement\DTO\UpdateProjectNotificationDTO;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectManagement\Presenters\ProjectNotificationPresenter;
use Modules\Project\ProjectManagement\Requests\CreateProjectNotificationRequest;
use Modules\Project\ProjectManagement\Requests\UpdateProjectNotificationRequest;
use PHPUnit\Framework\TestCase;

class ProjectNotificationAddressFieldsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $app = new Application(dirname(__DIR__, 6));
        $app->instance('config', new ConfigRepository(['app.locale' => 'en']));
    }

    public function test_address_fields_are_mapped_by_create_and_update_dtos(): void
    {
        $create = new CreateProjectNotificationDTO(
            projectId: 'project-id',
            createdByUserId: 'creator-id',
            assignedUserId: 'assignee-id',
            taskDate: '2026-07-13',
            taskTime: '17:35',
            durationHours: 1,
            taskLatitude: 30.0444,
            taskLongitude: 31.2357,
            district: 'Nasr City',
            fullAddress: '10 Mostafa El-Nahas Street, Cairo',
        );

        $this->assertSame('Nasr City', $create->toArray()['district']);
        $this->assertSame('10 Mostafa El-Nahas Street, Cairo', $create->toArray()['full_address']);

        $this->assertSame([], (new UpdateProjectNotificationDTO())->toArray());
        $this->assertSame([
            'district' => 'Heliopolis',
            'full_address' => '20 El-Orouba Road, Cairo',
        ], (new UpdateProjectNotificationDTO(
            district: 'Heliopolis',
            fullAddress: '20 El-Orouba Road, Cairo',
        ))->toArray());
    }

    public function test_address_validation_rules_match_create_and_partial_update_contracts(): void
    {
        $createRules = (new CreateProjectNotificationRequest())->rules();
        $updateRules = (new UpdateProjectNotificationRequest())->rules();

        $this->assertSame(['required', 'string', 'max:255'], $createRules['district']);
        $this->assertSame(['required', 'string'], $createRules['full_address']);
        $this->assertSame(['sometimes', 'required', 'string', 'max:255'], $updateRules['district']);
        $this->assertSame(['sometimes', 'required', 'string'], $updateRules['full_address']);
    }

    public function test_presenter_returns_address_fields_for_detail_list_and_legacy_records(): void
    {
        $notification = new ProjectNotification([
            'district' => 'Nasr City',
            'full_address' => '10 Mostafa El-Nahas Street, Cairo',
            'status' => 'pending',
        ]);

        $presenter = new ProjectNotificationPresenter($notification);

        $this->assertSame('Nasr City', $presenter->toArray()['district']);
        $this->assertSame('10 Mostafa El-Nahas Street, Cairo', $presenter->toArray()['full_address']);
        $this->assertSame('Nasr City', $presenter->toListArray()['district']);
        $this->assertSame('10 Mostafa El-Nahas Street, Cairo', $presenter->toListArray()['full_address']);

        $legacy = new ProjectNotification(['status' => 'pending']);
        $legacyPayload = (new ProjectNotificationPresenter($legacy))->toArray();

        $this->assertNull($legacyPayload['district']);
        $this->assertNull($legacyPayload['full_address']);
    }
}
