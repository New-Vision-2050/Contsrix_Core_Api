<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Imports;

/**
 * Exact first-row Header from the official UDS Excel template.
 *
 * Source of truth: the official template file, including trailing spaces.
 * This is used only for Header validation — not for Excel-to-database mapping.
 */
final class UdsExcelOfficialHeader
{
    public const COLUMN_COUNT = 38;

    public const TEMPLATE_RELATIVE_PATH = 'modules/Project/ProjectType/Resources/templates/uds-excel-template.xlsx';

    public const DOWNLOAD_NAME = 'uds-excel-template.xlsx';

    /**
     * @var list<string>
     */
    public const COLUMNS = [
        'Penalty Amount',
        'Finance Approval Date',
        'Certificate Source Number',
        'Modified Employee Number',
        'Contractor Assigned Employee Number',
        'Order Permit Status',
        'Order Permit Position',
        'Penalty Percentage',
        'Delay Duration',
        'Disbursement Status',
        'Total Cost',
        'Indirect Cost',
        'Labor Cost',
        'Unpaid Material Cost',
        'Paid Material Cost',
        'Office Code',
        'Current Entity',
        'Cost Center Name',
        'Cost Center',
        'Extract Number',
        'Completion Certificate Amount',
        'Contractor Approval Date for Completion Certificate',
        'Completion Certificate Approval Date',
        'Completion Certificate Date',
        'Received from Contractor Date',
        'Delivered to Contractor Date',
        'Action 203 Date',
        'Executing Entity',
        'Last Action Date',
        'Last Action Name',
        'Last Action Code',
        'Order Permit Type',
        'Contract Number',
        'Subscriber Type',
        'Order Permit Number',
        'Order Permit Type Code',
        'Contractor',
        'Office',
    ];

    public static function templateAbsolutePath(): string
    {
        return base_path(self::TEMPLATE_RELATIVE_PATH);
    }
}
