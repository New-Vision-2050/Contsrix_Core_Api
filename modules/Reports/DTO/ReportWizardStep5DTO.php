<?php

declare(strict_types=1);

namespace Modules\Reports\DTO;

/**
 * Step 5 — Sorting / display / delivery (المظاهر والخيارات).
 */
final class ReportWizardStep5DTO
{
    public function __construct(
        public readonly string $mainSortBy,
        public readonly string $sortDirection,
        public readonly string $groupBy,
        public readonly string $employeesPerPage,
        /** @var string[] */
        public readonly array  $visualElementIds,
        public readonly bool   $autoEmail,
        public readonly bool   $copyToManager,
        public readonly bool   $monthlyScheduling,
        public readonly bool   $companyHeaderFooter,
        public readonly bool   $digitalSignature,
        public readonly string $recipientEmails,
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            mainSortBy:          (string) ($payload['mainSortBy']       ?? 'employee_name_alpha'),
            sortDirection:       (string) ($payload['sortDirection']    ?? 'asc'),
            groupBy:             (string) ($payload['groupBy']          ?? 'none'),
            employeesPerPage:    (string) ($payload['employeesPerPage'] ?? '25'),
            visualElementIds:    array_values($payload['visualElementIds'] ?? []),
            autoEmail:           (bool) ($payload['autoEmail']           ?? false),
            copyToManager:       (bool) ($payload['copyToManager']       ?? false),
            monthlyScheduling:   (bool) ($payload['monthlyScheduling']   ?? false),
            companyHeaderFooter: (bool) ($payload['companyHeaderFooter'] ?? true),
            digitalSignature:    (bool) ($payload['digitalSignature']    ?? false),
            recipientEmails:     (string) ($payload['recipientEmails']   ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'mainSortBy'          => $this->mainSortBy,
            'sortDirection'       => $this->sortDirection,
            'groupBy'             => $this->groupBy,
            'employeesPerPage'    => $this->employeesPerPage,
            'visualElementIds'    => $this->visualElementIds,
            'autoEmail'           => $this->autoEmail,
            'copyToManager'       => $this->copyToManager,
            'monthlyScheduling'   => $this->monthlyScheduling,
            'companyHeaderFooter' => $this->companyHeaderFooter,
            'digitalSignature'    => $this->digitalSignature,
            'recipientEmails'     => $this->recipientEmails,
        ];
    }

    /**
     * Parse comma/semicolon/whitespace-separated emails into a normalised array.
     *
     * @return string[]
     */
    public function getRecipientEmailsList(): array
    {
        if ($this->recipientEmails === '') {
            return [];
        }

        $parts = preg_split('/[\s,;]+/', $this->recipientEmails) ?: [];
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts, static fn (string $email): bool => $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
        return array_values(array_unique($parts));
    }
}
