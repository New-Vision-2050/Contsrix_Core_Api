<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Imports;

final class UdsExcelHeaderValidator
{
    /**
     * @param  array<int|string, mixed>  $headerRow
     */
    public function validate(array $headerRow): void
    {
        $received = $this->extractHeaderValues($headerRow);
        $expected = UdsExcelOfficialHeader::COLUMNS;
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

        $comparable = min($receivedCount, $expectedCount);
        for ($index = 0; $index < $comparable; $index++) {
            if ($received[$index] === $expected[$index]) {
                continue;
            }

            $position = $index + 1;

            if ($received[$index] === '') {
                $mismatches[] = "Missing column at position {$position}:\nExpected: {$expected[$index]}";
                continue;
            }

            $mismatches[] = "Column {$position}:\nExpected: {$expected[$index]}\nReceived: {$received[$index]}";
        }

        if ($mismatches === []) {
            return;
        }

        $message = "Invalid UDS template.\n\nThe uploaded Excel Header does not match the official UDS template.\n\n"
            . implode("\n\n", $mismatches);

        throw new InvalidUdsExcelHeaderException($message, $mismatches);
    }

    /**
     * Keep Header text exactly as-is (including trailing spaces).
     * Drop only trailing empty Excel cells so padding does not count as extra columns.
     *
     * @param  array<int|string, mixed>  $headerRow
     * @return list<string>
     */
    private function extractHeaderValues(array $headerRow): array
    {
        $values = [];

        foreach (array_values($headerRow) as $value) {
            if ($value === null) {
                $values[] = '';
                continue;
            }

            $values[] = is_scalar($value) ? (string) $value : '';
        }

        while ($values !== [] && $values[array_key_last($values)] === '') {
            array_pop($values);
        }

        return $values;
    }
}
