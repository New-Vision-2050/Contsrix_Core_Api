<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Tests\Unit\Enums;

use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Tests\TestCase;

/**
 * Contract tests for EmployeeTaskStatus enum.
 *
 * Ensures the string values never silently change (they are stored in the DB),
 * labels are non-empty, and the helper methods return the correct sets.
 */
final class EmployeeTaskStatusTest extends TestCase
{
    /** DB values must never change — they are stored in employee_task_requests.status. */
    public function test_db_values_are_stable(): void
    {
        $this->assertSame('pending',     EmployeeTaskStatus::Pending->value);
        $this->assertSame('approved',    EmployeeTaskStatus::Approved->value);
        $this->assertSame('rejected',    EmployeeTaskStatus::Rejected->value);
        $this->assertSame('in_progress', EmployeeTaskStatus::InProgress->value);
        $this->assertSame('paused',      EmployeeTaskStatus::Paused->value);
        $this->assertSame('completed',   EmployeeTaskStatus::Completed->value);
        $this->assertSame('cancelled',   EmployeeTaskStatus::Cancelled->value);
    }

    /** Every case must have a non-empty Arabic label. */
    public function test_arabic_labels_are_non_empty(): void
    {
        foreach (EmployeeTaskStatus::cases() as $case) {
            $this->assertNotEmpty(
                $case->label('ar'),
                "Arabic label missing for case: {$case->name}"
            );
        }
    }

    /** Every case must have a non-empty English label. */
    public function test_english_labels_are_non_empty(): void
    {
        foreach (EmployeeTaskStatus::cases() as $case) {
            $this->assertNotEmpty(
                $case->label('en'),
                "English label missing for case: {$case->name}"
            );
        }
    }

    /** values() must return exactly 7 strings matching all case values. */
    public function test_values_returns_all_case_values(): void
    {
        $values = EmployeeTaskStatus::values();

        $this->assertCount(7, $values);
        $this->assertContains('pending',     $values);
        $this->assertContains('approved',    $values);
        $this->assertContains('rejected',    $values);
        $this->assertContains('in_progress', $values);
        $this->assertContains('paused',      $values);
        $this->assertContains('completed',   $values);
        $this->assertContains('cancelled',   $values);
    }

    public function test_active_statuses_contains_in_progress_and_paused_only(): void
    {
        $active = EmployeeTaskStatus::activeStatuses();

        $this->assertCount(2, $active);
        $this->assertContains('in_progress', $active);
        $this->assertContains('paused',      $active);
    }

    public function test_terminal_statuses_contains_completed_cancelled_rejected(): void
    {
        $terminal = EmployeeTaskStatus::terminalStatuses();

        $this->assertCount(3, $terminal);
        $this->assertContains('completed',  $terminal);
        $this->assertContains('cancelled',  $terminal);
        $this->assertContains('rejected',   $terminal);
    }

    /** Active and terminal sets must be disjoint. */
    public function test_active_and_terminal_statuses_are_disjoint(): void
    {
        $overlap = array_intersect(
            EmployeeTaskStatus::activeStatuses(),
            EmployeeTaskStatus::terminalStatuses()
        );

        $this->assertEmpty($overlap);
    }

    /** from() must work for every known DB value. */
    public function test_from_works_for_all_values(): void
    {
        foreach (EmployeeTaskStatus::values() as $value) {
            $case = EmployeeTaskStatus::from($value);
            $this->assertSame($value, $case->value);
        }
    }
}
