<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Unit;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Modules\Project\ProjectManagement\DTO\CreateProjectNotificationDTO;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectManagement\Presenters\ProjectNotificationPresenter;
use Modules\Project\ProjectManagement\Requests\CreateProjectNotificationRequest;
use PHPUnit\Framework\TestCase;

class ProjectNotificationAddressFieldsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $app = new Application(dirname(__DIR__, 6));
        $app->instance('config', new ConfigRepository(['app.locale' => 'en']));
    }

    public function test_create_request_and_dto_include_address_fields(): void
    {
        $rules = (new CreateProjectNotificationRequest())->rules();

        $this->assertContains('string', $rules['district']);
        $this->assertContains('max:255', $rules['district']);
        $this->assertContains('string', $rules['full_address']);

        $dto = new CreateProjectNotificationDTO(
            createdByUserId: 'creator-id',
            district: 'Nasr City',
            fullAddress: '10 Mostafa El-Nahas Street, Cairo',
        );

        $this->assertSame('Nasr City', $dto->toArray()['district']);
        $this->assertSame('10 Mostafa El-Nahas Street, Cairo', $dto->toArray()['full_address']);
    }

    public function test_presenter_returns_address_fields_in_detail_and_list(): void
    {
        $notification = new ProjectNotification([
            'district' => 'Nasr City',
            'full_address' => '10 Mostafa El-Nahas Street, Cairo',
            'status' => 'draft',
        ]);

        $presenter = new ProjectNotificationPresenter($notification);

        $this->assertSame('Nasr City', $presenter->toArray()['district']);
        $this->assertSame('10 Mostafa El-Nahas Street, Cairo', $presenter->toArray()['full_address']);
        $this->assertSame('Nasr City', $presenter->toListArray()['district']);
        $this->assertSame('10 Mostafa El-Nahas Street, Cairo', $presenter->toListArray()['full_address']);
    }
}
