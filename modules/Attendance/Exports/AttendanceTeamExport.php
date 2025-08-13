<?php

namespace Modules\Attendance\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Models\Attendance;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;
class AttendanceTeamExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    private AttendanceService $attendanceService;
    private array $filters;

    public function __construct(AttendanceService $attendanceService, array $filters = [])
    {
        $this->attendanceService = $attendanceService;
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // This method will call the service method we just created to get the processed data
        return $this->attendanceService->getAttendanceForExport($this->filters);
    }

    /**
     * Define the column headings for the export file.
     * @return array
     */
    public function headings(): array
    {
        return [
            'User Name',
            'User Email',
            'Company Name',
            'Date',
            'Status',
            'Clock In Time',
            'Clock Out Time',
            'Is Late',
            'Is Absent',
            'Is Holiday',
            'Day Status',
            'Overtime Hours',
            'Clock In Location',
            'Location Tracking',
        ];
    }

    /**
     * Map each attendance record to a row in the spreadsheet.
     * The $attendanceRecord here will be one of the "representative" records from your
     * processAttendancePeriods method.
     *
     * @param Attendance $attendanceRecord
     * @return array
     */
    public function map($attendanceRecord): array
    {
        // Ensure you're accessing properties that exist on the representative record
        // and its eager-loaded relationships (user, user.company).
        $userName = $attendanceRecord->user->full_name ?? $attendanceRecord->user->name ?? 'N/A';
        $userEmail = $attendanceRecord->user->email ?? 'N/A';
        $companyName = $attendanceRecord->user->company->name ?? 'N/A'; // Assuming user->company relationship is loaded

        return [
            $userName,
            $userEmail,
            $companyName,
            Carbon::parse($attendanceRecord->start_time)->toDateString(),
            ucfirst($attendanceRecord->status ?? 'N/A'), // Example: make first letter uppercase
            $attendanceRecord->clock_in_time ? Carbon::parse($attendanceRecord->clock_in_time)->format('H:i:s') : '-',
            $attendanceRecord->clock_out_time ? Carbon::parse($attendanceRecord->clock_out_time)->format('H:i:s') : '-',
            $attendanceRecord->is_late ? 'Yes' : 'No',
            $attendanceRecord->is_absent ? 'Yes' : 'No',
            $attendanceRecord->is_holiday ? 'Yes' : 'No',
            ucfirst($attendanceRecord->day_status ?? 'N/A'),
            (string)($attendanceRecord->overtime_hours ?? 0), // Cast to string to prevent Excel auto-formatting issues
            $attendanceRecord->clock_in_location ?? '-',
            $attendanceRecord->location_tracking ?? '-',
        ];
    }

    /**
     * Apply styles to the worksheet.
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        // Optional: Set RTL direction if you primarily deal with right-to-left languages (e.g., Arabic)
        // $sheet->setRightToLeft(true);

        return [
            // Style the first row (headings)
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'] // Light grey background for header
                ]
            ]
        ];
    }
}
