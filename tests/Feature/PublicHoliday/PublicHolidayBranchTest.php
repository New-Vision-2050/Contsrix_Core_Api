<?php

declare(strict_types=1);

namespace Tests\Feature\PublicHoliday;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Modules\Attendance\Repositories\AttendanceReportRepository;
use Modules\Leave\PublicHoliday\Commands\UpdatePublicHolidayCommand;
use Modules\Leave\PublicHoliday\DTO\CreatePublicHolidayDTO;
use Modules\Leave\PublicHoliday\Handlers\UpdatePublicHolidayHandler;
use Modules\Leave\PublicHoliday\Models\PublicHoliday;
use Modules\Leave\PublicHoliday\Presenters\PublicHolidayPresenter;
use Modules\Leave\PublicHoliday\Repositories\PublicHolidayRepository;
use Modules\Leave\PublicHoliday\Requests\CreatePublicHolidayRequest;
use Modules\Leave\PublicHoliday\Services\PublicHolidayCRUDService;
use Ramsey\Uuid\Uuid;
use PHPUnit\Framework\TestCase;

class PublicHolidayBranchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $app = new \Illuminate\Foundation\Application(dirname(__DIR__, 3));
        $app->instance('config', new \Illuminate\Config\Repository([
            'database' => ['default' => 'sqlite', 'connections' => ['sqlite' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
            ]]],
            'app' => ['locale' => 'en'],
        ]));
        \Illuminate\Support\Facades\Facade::clearResolvedInstances();
        \Illuminate\Support\Facades\Facade::setFacadeApplication($app);
        $app->register(\Illuminate\Events\EventServiceProvider::class);
        $app->register(\Illuminate\Database\DatabaseServiceProvider::class);
        $app->register(\Illuminate\Translation\TranslationServiceProvider::class);
        $app->register(\Illuminate\Validation\ValidationServiceProvider::class);
        $app->instance('files', new \Illuminate\Filesystem\Filesystem());
        $app->instance('request', \Illuminate\Http\Request::create('/'));
        $app->instance(\Stancl\Tenancy\Tenancy::class, new class {
            public bool $initialized = false;
            public $tenant = null;
        });
        $app->instance(\Illuminate\Contracts\Bus\Dispatcher::class, new \Illuminate\Support\Testing\Fakes\BusFake(new \Illuminate\Bus\Dispatcher($app)));
        $app->boot();

        Schema::create('management_hierarchies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('company_id')->nullable();
            $table->string('manager_id')->nullable();
        });
        Schema::create('users', fn (Blueprint $table) => $table->string('id')->primary());
        $path = base_path('modules/Leave/PublicHoliday/Database/Migrations');
        foreach (glob($path . '/*.php') as $file) {
            (require $file)->up();
        }
        DB::table('management_hierarchies')->insert([
            ['id' => 1, 'name' => 'Cairo', 'type' => 'branch'],
            ['id' => 2, 'name' => 'Alexandria', 'type' => 'branch'],
            ['id' => 3, 'name' => 'Finance', 'type' => 'department'],
        ]);
    }

    public function test_branch_is_required_and_departments_are_rejected(): void
    {
        $rules = (new CreatePublicHolidayRequest())->rules();
        $data = ['name' => 'Holiday', 'date_start' => '09-24', 'date_end' => '09-24'];
        $this->assertTrue(Validator::make($data + ['country_id' => 1], $rules)->fails());
        $this->assertTrue(Validator::make($data + ['branch_id' => 3], $rules)->fails());
        $this->assertTrue(Validator::make($data + ['branch_id' => 999], $rules)->fails());
        $this->assertFalse(Validator::make($data + ['branch_id' => 1], $rules)->fails());
    }

    public function test_create_update_and_list_return_branch_and_applied_days_count(): void
    {
        $service = app(PublicHolidayCRUDService::class);
        $holiday = $service->create(new CreatePublicHolidayDTO(
            'Branch holiday', 1, new \DateTime('2026-09-24'), new \DateTime('2026-09-26'),
        ));
        $data = (new PublicHolidayPresenter($holiday))->getData();
        $this->assertSame('09-24', $data['date_start']);
        $this->assertSame('09-26', $data['date_end']);
        $this->assertSame(2026, $data['year']);
        $this->assertSame(1, $data['branch_id']);
        $this->assertSame('Cairo', $data['branch']['name']);
        $this->assertSame(5, $data['count_days']);
        $this->assertCount(5, $data['days']);
        $this->assertArrayNotHasKey('country_id', $data);
        $this->assertNull($holiday->country_id);
        Bus::assertNothingDispatched();

        app(UpdatePublicHolidayHandler::class)->handle(new UpdatePublicHolidayCommand(
            Uuid::fromString($holiday->id), 'Updated', 2,
            new \DateTime('2026-09-27'), new \DateTime('2026-09-27'),
        ));
        $updated = (new PublicHolidayPresenter($service->get(Uuid::fromString($holiday->id))))->getData();
        $this->assertSame(2, $updated['branch_id']);
        $this->assertSame(1, $updated['count_days']);
        $this->assertSame('Alexandria', $updated['branch']['name']);

        $this->assertCount(0, app(PublicHolidayRepository::class)->getForExport(['branch_id' => 1]));
        $this->assertCount(1, app(PublicHolidayRepository::class)->getForExport(['branch_id' => 2]));

        request()->merge(['branch_id' => 1]);
        $this->assertCount(0, app(PublicHolidayRepository::class)->paginatedWithConditions()['data']);
        request()->merge(['branch_id' => 2]);
        $list = app(PublicHolidayRepository::class)->paginatedWithConditions()['data'];
        $this->assertSame(1, PublicHolidayPresenter::collection($list)[0]['count_days']);
    }

    public function test_month_day_request_validation_and_conversion(): void
    {
        $request = new CreatePublicHolidayRequest();
        $data = ['name' => 'Annual', 'branch_id' => 1, 'date_start' => '12-31', 'date_end' => '01-02'];
        $validator = Validator::make($data, $request->rules());
        $this->assertFalse($validator->fails());
        $request->setValidator($validator);
        $dto = $request->createCreatePublicHolidayDTO();
        $this->assertSame(now()->year . '-12-31', $dto->date_start->format('Y-m-d'));
        $this->assertSame((now()->year + 1) . '-01-02', $dto->date_end->format('Y-m-d'));
        foreach (['2026-09-24', '13-01', '04-31', '2-03'] as $invalid) {
            $this->assertTrue(Validator::make(array_replace($data, ['date_start' => $invalid]), $request->rules())->fails());
        }
        $this->assertFalse(Validator::make(array_replace($data, ['date_start' => '02-29']), $request->rules())->fails());
    }

    public function test_annual_generation_is_idempotent_recalculates_days_and_handles_leap_years(): void
    {
        $repository = app(PublicHolidayRepository::class);
        $source = $repository->createPublicHoliday(['name' => 'Annual', 'branch_id' => 1, 'date_start' => '2024-09-24', 'date_end' => '2024-09-26']);
        $leap = $repository->createPublicHoliday(['name' => 'Leap', 'branch_id' => 1, 'date_start' => '2024-02-29', 'date_end' => '2024-02-29']);
        $repository->createPublicHoliday(['name' => 'Disabled', 'branch_id' => 1, 'date_start' => '2024-06-01', 'date_end' => '2024-06-01', 'is_active' => false]);
        $generator = app(\Modules\Leave\PublicHoliday\Services\GenerateAnnualPublicHolidays::class);
        $this->assertSame(1, $generator->execute(2026));
        $this->assertSame(0, $generator->execute(2026));
        $copy = PublicHoliday::where('recurrence_source_id', $source->id)->where('year', 2026)->firstOrFail();
        $this->assertSame('2026-09-24', $copy->date_start->format('Y-m-d'));
        $this->assertSame(5, $copy->days()->count());
        $this->assertSame(2, $generator->execute(2028));
        $this->assertSame(1, PublicHoliday::where('recurrence_source_id', $leap->id)->count());
        $this->assertSame(0, $generator->execute(2028));
        $this->assertSame(0, PublicHoliday::where('recurrence_source_id', $copy->id)->count());
    }

    public function test_branch_cards_include_available_years_and_empty_branches_only_for_current_company(): void
    {
        DB::table('management_hierarchies')->insert([
            'id' => 4, 'name' => 'Other company', 'type' => 'branch', 'company_id' => 'other-company',
        ]);
        $repository = app(PublicHolidayRepository::class);
        $repository->createPublicHoliday(['name' => 'Across years', 'branch_id' => 1, 'date_start' => '2023-12-31', 'date_end' => '2024-01-01']);
        $repository->createPublicHoliday(['name' => 'Same year', 'branch_id' => 1, 'date_start' => '2024-06-01', 'date_end' => '2024-06-01']);
        $repository->createPublicHoliday(['name' => 'Hidden', 'branch_id' => 4, 'date_start' => '2025-01-01', 'date_end' => '2025-01-01']);

        $cards = collect(app(PublicHolidayCRUDService::class)->branchCards())->keyBy('branch_id');
        $this->assertCount(2, $cards);
        $this->assertSame([2023, 2024], $cards[1]['years']);
        $this->assertSame([], $cards[2]['years']);
    }

    public function test_list_filters_by_selected_year_and_month_including_overlapping_holidays(): void
    {
        $repository = app(PublicHolidayRepository::class);
        $holiday = $repository->createPublicHoliday(['name' => 'Across years', 'branch_id' => 1, 'date_start' => '2023-12-31', 'date_end' => '2024-01-02']);
        $repository->createPublicHoliday(['name' => 'Later', 'branch_id' => 1, 'date_start' => '2024-02-01', 'date_end' => '2024-02-01']);
        $service = app(PublicHolidayCRUDService::class);
        $list = $service->list(filters: ['year' => 2024, 'month' => 1]);
        $this->assertSame([$holiday->id], $list['data']->pluck('id')->all());
        $this->assertCount(2, $service->list(filters: ['year' => 2024])['data']);
        $this->assertCount(0, $service->list(filters: ['year' => 2025])['data']);
    }

    public function test_live_calendar_resolves_branch_holidays_and_legacy_country_without_attendance_jobs(): void
    {
        $repository = app(PublicHolidayRepository::class);
        foreach ([[1, 10, '2026-09-24'], [2, 10, '2026-09-25'], [null, 10, '2026-09-26']] as [$branch, $country, $date]) {
            $holiday = $repository->createPublicHoliday([
                'name' => 'Holiday', 'branch_id' => $branch, 'country_id' => $country,
                'date_start' => $date, 'date_end' => $date,
            ]);
            $repository->syncPublicHolidayDays($holiday, [['date' => Carbon::parse($date), 'is_compensation' => false]]);
        }
        $calendar = app(\Modules\Attendance\Services\PublicHolidayCalendarService::class);
        $days = $calendar->forCountry('10', '2026-09-01', '2026-09-30', 1);
        $this->assertTrue($days->isHoliday('2026-09-24'));
        $this->assertTrue($days->isHoliday('2026-09-26'));
        $this->assertFalse($days->isHoliday('2026-09-25'));
        $this->assertTrue($calendar->forCountry(null, '2026-09-01', '2026-09-30', 1)->isHoliday('2026-09-24'));
        $this->assertFalse($calendar->forCountry('10', '2026-09-01', '2026-09-30')->isHoliday('2026-09-24'));

        $user = (new \Modules\User\Models\User())->setRelation('userProfessionalData',
            (new \Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData())->setRawAttributes(['branch_id' => 1]));
        $user->setRelation('branch', (new \Modules\Company\ManagementHierarchy\Models\ManagementHierarchy())->setRawAttributes(['id' => 2]));
        $this->assertSame(1, $calendar->branchIdForUser($user));
        $user->setRelation('userProfessionalData', null);
        $this->assertSame(2, $calendar->branchIdForUser($user));
        Bus::assertNothingDispatched();
    }

    public function test_attendance_counts_only_the_requested_branch_and_legacy_country_days(): void
    {
        $service = app(PublicHolidayCRUDService::class);
        foreach ([1, 2] as $branch) {
            $service->create(new CreatePublicHolidayDTO(
                'Branch holiday', $branch, new \DateTime($branch === 1 ? '2026-09-27' : '2026-09-20'),
                new \DateTime($branch === 1 ? '2026-09-27' : '2026-09-20'),
            ));
        }
        // An updated legacy holiday may retain country metadata; its branch takes precedence.
        PublicHoliday::where('branch_id', 2)->update(['country_id' => 10]);
        $legacy = PublicHoliday::create(['name' => 'Legacy', 'country_id' => 10, 'date_start' => '2026-09-28', 'date_end' => '2026-09-28']);
        app(PublicHolidayRepository::class)->syncPublicHolidayDays($legacy, [
            ['date' => Carbon::parse('2026-09-28'), 'is_compensation' => false],
        ]);
        $repository = app(AttendanceReportRepository::class);
        $this->assertSame(2, $repository->countAllPublicHolidayDaysInPeriod('2026-09-01', '2026-09-30', '10', 1));
        $this->assertSame(1, $repository->countAllPublicHolidayDaysInPeriod('2026-09-01', '2026-09-30', null, 1));
        $this->assertSame(0, $repository->countAllPublicHolidayDaysInPeriod('2026-09-01', '2026-09-30', null, 3));
    }
}
