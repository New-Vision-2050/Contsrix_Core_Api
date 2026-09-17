<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Imports;

final class WorkOrderExcelHeaderValidator
{
    /**
     * @param  array<int|string, mixed>  $headerRow
     */
    public function validate(array $headerRow): void
    {
        $received = $this->extractHeaderValues($headerRow);
        $expected = WorkOrderExcelOfficialHeader::COLUMNS;
        $mismatches = [];

        $receivedCount = count($received);
        $expectedCount = count($expected);

        if ($receivedCount < $expectedCount) {
            for ($index = $receivedCount; $index < $expectedCount; $index++) {
                $position = $index + 1;
                $mismatches[] = "Missing column at position {$position}:\nExpected: {$expected[$index]}";
            }
        }

        if ($receivedCount > $expectedCount) {
            for ($index = $expectedCount; $index < $receivedCount; $index++) {
                $position = $index + 1;
                $mismatches[] = "Unexpected column at position {$position}:\nReceived: {$received[$index]}\nExpected: (none)";
            }
        }

        for ($index = 0, $count = min($receivedCount, $expectedCount); $index < $count; $index++) {
            if ($received[$index] === $expected[$index]) {
                continue;
            }

            $position = $index + 1;
            $mismatches[] = $received[$index] === ''
                ? "Missing column at position {$position}:\nExpected: {$expected[$index]}"
                : "Column {$position}:\nExpected: {$expected[$index]}\nReceived: {$received[$index]}";
        }

        if ($mismatches === []) {
            return;
        }

        $message = "Invalid Work Orders template.\n\nThe uploaded Excel header does not match the official Work Orders template.\n\n"
            .implode("\n\n", $mismatches);

        throw new InvalidWorkOrderExcelHeaderException($message, $mismatches);
    }

    /**
     * @param  array<int|string, mixed>  $headerRow
     * @return list<string>
     */
    private function extractHeaderValues(array $headerRow): array
    {
        $values = array_map(
            static fn (mixed $value): string => is_scalar($value) ? (string) $value : '',
            array_values($headerRow),
        );

        while ($values !== [] && $values[array_key_last($values)] === '') {
            array_pop($values);
        }

        return $values;
    }
}
