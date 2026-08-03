<?php

namespace App\Exports\Sheets;

use App\Models\Timesheet;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Facades\DB;

class ClockFileExport implements FromQuery, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    public function query()
    {
        return Timesheet::query()
            ->join('employees', 'timesheets.employee_id', '=', 'employees.id')
            ->select(
                'employees.id as employee_id',
                'employees.employee_number',
                'employees.first_name',
                DB::Raw('SUM(normal_time_hours) as normal_time_hours'),
                DB::Raw('SUM(overtime_1_3_3_hours) as overtime_1_3_3_hours'),
                DB::Raw('SUM(overtime_1_5_hours) as overtime_1_5_hours'),
                DB::Raw('SUM(overtime_2_0_hours) as overtime_2_0_hours'),
                DB::Raw('SUM(overtime_2_5_hours) as overtime_2_5_hours')
            )
            ->groupBy('employees.id', 'employees.employee_number', 'employees.first_name');
    }

    public function title(): string
    {
        return 'Raw Clock File';
    }

    public function headings(): array
    {
        return [
            'Employee No.',
            'Employee Name',
            'Normal Time',
            'OT 1.33',
            'OT 1.5',
            'OT 2.0',
            'OT 2.5',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee_number,
            $row->first_name,
            $row->normal_time_hours ?? 0,
            $row->overtime_1_3_3_hours ?? 0,
            $row->overtime_1_5_hours ?? 0,
            $row->overtime_2_0_hours ?? 0,
            $row->overtime_2_5_hours ?? 0,
        ];
    }
}